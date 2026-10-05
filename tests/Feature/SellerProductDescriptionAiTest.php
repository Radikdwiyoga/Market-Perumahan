<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\ProductDescriptionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductDescriptionAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google_ai.key' => 'test-key',
            'services.google_ai.model' => 'gemini-3.8-flash',
            'services.google_ai.fallback_models' => [],
        ]);
    }

    public function test_seller_can_generate_a_description_from_an_uploaded_photo(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Keripik singkong goreng, renyah dan berwarna keemasan.']]]],
                ],
            ]),
        ]);

        $seller = $this->seller();
        $category = Category::create(['name' => 'Camilan']);

        $response = $this->actingAs($seller)->postJson(route('seller.products.ai-description'), [
            'image' => UploadedFile::fake()->image('keripik.jpg'),
            'name' => 'Keripik Singkong',
            'category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('description', 'Keripik singkong goreng, renyah dan berwarna keemasan.');

        Http::assertSent(function ($request) {
            $parts = $request['contents'][0]['parts'];

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key=test-key'
                && data_get($parts, '0.inline_data.mime_type') === 'image/jpeg'
                && str_contains(data_get($parts, '1.text'), 'Keripik Singkong')
                && str_contains(data_get($parts, '1.text'), 'Camilan');
        });
    }

    public function test_seller_can_generate_a_description_from_the_existing_product_photo(): void
    {
        Storage::fake('public');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Gula pasir putih.']]]]],
            ]),
        ]);

        $seller = $this->seller();
        $store = $seller->sellerProfile;
        $category = Category::create(['name' => 'Sembako']);
        Storage::disk('public')->put('products/gula.webp', 'dummy-bytes');
        $product = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Gula Pasir',
            'image' => 'products/gula.webp',
            'price' => 17000,
            'stock' => 8,
        ]);

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('description', 'Gula pasir putih.');
    }

    public function test_seller_cannot_send_another_stores_product_photo_to_the_ai(): void
    {
        Storage::fake('public');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Deskripsi dari nama saja.']]]]],
            ]),
        ]);

        $seller = $this->seller();
        $category = Category::create(['name' => 'Sembako']);
        Storage::disk('public')->put('products/milik-orang.webp', 'rahasia-toko-lain');

        $otherSeller = User::factory()->create(['role' => 'seller']);
        $foreignStore = SellerProfile::create([
            'user_id' => $otherSeller->id,
            'store_name' => 'Toko Orang Lain',
            'phone' => $otherSeller->phone,
            'address' => 'Blok Z9',
        ]);

        $foreignProduct = Product::create([
            'seller_profile_id' => $foreignStore->id,
            'category_id' => $category->id,
            'name' => 'Produk Orang Lain',
            'image' => 'products/milik-orang.webp',
            'price' => 10000,
            'stock' => 3,
        ]);

        // `product_id` milik toko lain tidak resolving ke file apa pun, jadi request
        // ditolak tanpa pernah mengirim foto toko lain ke API AI.
        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'product_id' => $foreignProduct->id,
                'name' => 'Kopi Bubuk',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        Http::assertNothingSent();
    }

    public function test_a_free_form_image_path_is_ignored(): void
    {
        Storage::fake('public');
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Deskripsi aman.']]]]],
            ]),
        ]);

        $seller = $this->seller();
        Storage::disk('public')->put('products/rahasia.webp', 'rahasia');

        // `existing_image` tidak lagi menjadi input yang dipercaya, sehingga path
        // bebas diabaikan dan tidak ada foto yang terkirim ke API AI.
        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), ['existing_image' => 'products/rahasia.webp'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        Http::assertNothingSent();
    }

    public function test_ai_markers_are_stripped_from_the_generated_description(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => "\"**Deskripsi**\n- Barang bekas\n- Kondisi baik\""]]]],
                ],
            ]),
        ]);

        $seller = $this->seller();

        $response = $this->actingAs($seller)->postJson(route('seller.products.ai-description'), [
            'image' => UploadedFile::fake()->image('barang.jpg'),
        ]);

        $response->assertOk();

        $description = $response->json('description');

        $this->assertStringNotContainsString('*', $description);
        $this->assertStringNotContainsString('-', $description);
        $this->assertStringNotContainsString('"', $description);
        $this->assertStringContainsString('Deskripsi', $description);
    }

    public function test_request_fails_when_no_photo_is_available(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pilih foto produk terlebih dahulu, atau simpan foto pada produk yang sudah ada.');
    }

    public function test_generator_falls_back_to_another_model_when_the_primary_is_busy(): void
    {
        config(['services.google_ai.fallback_models' => ['gemini-3.6-flash']]);

        Http::fake([
            '*/models/gemini-3.8-flash:generateContent*' => Http::response(['error' => 'busy'], 503),
            '*/models/gemini-3.6-flash:generateContent*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Karpet hijau tebal, motif repaid.']]]]],
            ]),
        ]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('karpet.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('description', 'Karpet hijau tebal, motif repaid.');

        Http::assertSentCount(2);
    }

    public function test_generator_stops_immediately_on_configuration_errors(): void
    {
        config(['services.google_ai.fallback_models' => ['gemini-3.6-flash']]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'forbidden'], 403),
        ]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'API key Google AI ditolak. Periksa kembali GOOGLE_AI_API_KEY.');

        Http::assertSentCount(1);
    }

    public function test_request_fails_when_the_configured_model_is_retired(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['code' => 404, 'status' => 'NOT_FOUND'],
            ], 404),
        ]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Model AI yang dikonfigurasi tidak tersedia. Periksa GOOGLE_AI_MODEL pada .env.');
    }

    public function test_request_fails_when_google_ai_returns_an_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Batas pemakaian API AI tercapai. Coba lagi beberapa menit lagi.');
    }

    public function test_request_fails_when_google_ai_rejects_the_api_key(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'forbidden'], 403),
        ]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'API key Google AI ditolak. Periksa kembali GOOGLE_AI_API_KEY.');
    }

    public function test_request_reports_when_the_feature_is_not_configured(): void
    {
        config(['services.google_ai.key' => null]);

        $seller = $this->seller();

        $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertStatus(503);
    }

    public function test_buyers_cannot_generate_descriptions(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_generated_description_stays_within_the_column_limit(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => str_repeat('deskripsi produk yang panjang. ', 300)]]]]],
            ]),
        ]);

        $seller = $this->seller();

        $description = $this->actingAs($seller)
            ->postJson(route('seller.products.ai-description'), [
                'image' => UploadedFile::fake()->image('barang.jpg'),
            ])
            ->assertOk()
            ->json('description');

        $this->assertLessThanOrEqual(2000, mb_strlen($description));
    }

    public function test_generator_reports_missing_configuration(): void
    {
        config(['services.google_ai.key' => null]);

        $this->assertFalse(ProductDescriptionGenerator::isConfigured());
    }

    private function seller(): User
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        return $seller;
    }
}
