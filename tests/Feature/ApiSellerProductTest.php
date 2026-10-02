<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiSellerProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_seller_product_api(): void
    {
        $this->getJson('/api/seller/products')->assertStatus(401);
        $this->getJson('/api/seller/products/1')->assertStatus(401);
        $this->postJson('/api/seller/products', [])->assertStatus(401);
        $this->putJson('/api/seller/products/1', [])->assertStatus(401);
        $this->deleteJson('/api/seller/products/1')->assertStatus(401);
    }

    public function test_buyer_cannot_use_the_seller_product_api(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer, 'sanctum')->getJson('/api/seller/products')->assertForbidden();
    }

    public function test_seller_can_create_a_product_via_api(): void
    {
        $seller = $this->seller('Warung Warga');
        $category = Category::create(['name' => 'Sembako']);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/products', [
                'category_id' => $category->id,
                'name' => 'Beras Pulen',
                'price' => 76000,
                'stock' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Beras Pulen')
            ->assertJsonPath('data.price', 76000);

        $this->assertDatabaseHas('products', ['name' => 'Beras Pulen', 'price' => 76000, 'stock' => 10]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PRODUCT_CREATED']);
    }

    public function test_seller_can_create_a_product_with_an_image_via_api(): void
    {
        Storage::fake('public');
        $seller = $this->seller('Warung Warga');
        $category = Category::create(['name' => 'Sembako']);

        $response = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/products', [
                'category_id' => $category->id,
                'name' => 'Sambal',
                'price' => 10000,
                'stock' => 5,
                'image' => UploadedFile::fake()->image('sambal.jpg'),
            ])
            ->assertCreated();

        $product = Product::query()->where('name', 'Sambal')->firstOrFail();
        Storage::disk('public')->assertExists($product->image);
        $this->assertStringContainsString('/storage/', $response->json('data.image_url'));
    }

    public function test_product_image_is_optimized_to_webp_via_api(): void
    {
        Storage::fake('public');
        $seller = $this->seller('Warung Warga');
        $category = Category::create(['name' => 'Sembako']);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/products', [
                'category_id' => $category->id,
                'name' => 'Kacang Atom',
                'price' => 8000,
                'stock' => 20,
                'image' => UploadedFile::fake()->image('kacang.png'),
            ])->assertCreated();

        $product = Product::query()->where('name', 'Kacang Atom')->firstOrFail();
        $this->assertStringEndsWith('.webp', $product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertSame('image/webp', getimagesize(Storage::disk('public')->path($product->image))['mime']);
    }

    public function test_seller_product_creation_validates_input(): void
    {
        $seller = $this->seller('Warung Warga');

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/products', ['name' => '', 'price' => -5, 'category_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'category_id']);
    }

    public function test_seller_sees_only_their_own_products(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $category = Category::create(['name' => 'Sembako']);
        $own = Product::create(['seller_profile_id' => $seller->sellerProfile()->first()->id, 'category_id' => $category->id, 'name' => 'Beras Sendiri', 'price' => 10000, 'stock' => 5]);
        Product::create(['seller_profile_id' => $otherSeller->sellerProfile()->first()->id, 'category_id' => $category->id, 'name' => 'Minyak Tetangga', 'price' => 20000, 'stock' => 5]);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    public function test_seller_can_update_their_own_product_via_api(): void
    {
        $seller = $this->seller('Warung Warga');
        $category = Category::create(['name' => 'Sembako']);
        $product = $this->product($seller, $category, 'Beras', 10000, 5);

        $this->actingAs($seller, 'sanctum')
            ->putJson('/api/seller/products/'.$product->id, [
                'category_id' => $category->id,
                'name' => 'Beras Premium',
                'price' => 12000,
                'stock' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Beras Premium');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Beras Premium', 'price' => 12000, 'stock' => 8]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PRODUCT_UPDATED']);
    }

    public function test_seller_cannot_update_another_stores_product(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $category = Category::create(['name' => 'Sembako']);
        $product = $this->product($otherSeller, $category, 'Minyak', 20000, 5);

        $this->actingAs($seller, 'sanctum')
            ->putJson('/api/seller/products/'.$product->id, ['category_id' => $category->id, 'name' => 'X', 'price' => 1, 'stock' => 1])
            ->assertNotFound();
    }

    public function test_seller_can_delete_their_own_product_via_api(): void
    {
        Storage::fake('public');
        $seller = $this->seller('Warung Warga');
        $category = Category::create(['name' => 'Sembako']);
        $product = $this->product($seller, $category, 'Beras', 10000, 5);
        $product->update(['image' => 'products/beras.jpg']);
        Storage::disk('public')->put('products/beras.jpg', 'fake');

        $this->actingAs($seller, 'sanctum')->deleteJson('/api/seller/products/'.$product->id)->assertOk();

        Storage::disk('public')->assertMissing('products/beras.jpg');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PRODUCT_DELETED']);
    }

    public function test_seller_cannot_delete_another_stores_product(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $category = Category::create(['name' => 'Sembako']);
        $product = $this->product($otherSeller, $category, 'Minyak', 20000, 5);

        $this->actingAs($seller, 'sanctum')->deleteJson('/api/seller/products/'.$product->id)->assertNotFound();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_seller_can_show_their_own_product_but_not_others(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $category = Category::create(['name' => 'Sembako']);
        $own = $this->product($seller, $category, 'Beras Sendiri', 10000, 5);
        $foreign = $this->product($otherSeller, $category, 'Minyak Tetangga', 20000, 5);

        $this->actingAs($seller, 'sanctum')->getJson('/api/seller/products/'.$own->id)->assertOk();
        $this->actingAs($seller, 'sanctum')->getJson('/api/seller/products/'.$foreign->id)->assertNotFound();
    }

    private function seller(string $storeName): User
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => $storeName, 'phone' => $seller->phone, 'address' => 'Blok A1']);

        return $seller;
    }

    private function product(User $seller, Category $category, string $name, int $price, int $stock): Product
    {
        return Product::create([
            'seller_profile_id' => $seller->sellerProfile()->first()->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
        ]);
    }
}
