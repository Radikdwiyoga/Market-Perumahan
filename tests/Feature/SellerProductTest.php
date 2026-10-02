<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_a_product_for_their_store(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);

        $response = $this->actingAs($seller)->post(route('seller.products.store'), [
            'category_id' => $category->id,
            'name' => 'Gula Pasir',
            'description' => 'Gula pasir satu kilogram',
            'price' => 17000,
            'stock' => 8,
        ]);

        $response->assertRedirect(route('seller.products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Gula Pasir',
            'seller_profile_id' => $seller->sellerProfile->id,
        ]);
    }

    public function test_buyer_cannot_access_seller_products(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('seller.products.index'))->assertForbidden();
    }

    public function test_seller_can_update_and_delete_their_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Gula Pasir',
            'price' => 17000,
            'stock' => 8,
        ]);

        $this->actingAs($seller)->put(route('seller.products.update', $product), [
            'category_id' => $category->id,
            'name' => 'Gula Premium',
            'description' => 'Gula premium satu kilogram',
            'price' => 19000,
            'stock' => 6,
        ])->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Gula Premium', 'price' => 19000]);

        $this->actingAs($seller)->delete(route('seller.products.destroy', $product))->assertRedirect(route('seller.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_seller_cannot_update_another_sellers_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $otherStore->id, 'category_id' => $category->id, 'name' => 'Produk B', 'price' => 1000, 'stock' => 1]);

        $this->actingAs($seller)->get(route('seller.products.edit', $product))->assertNotFound();
        $this->assertDatabaseHas('seller_profiles', ['id' => $store->id]);
    }

    public function test_seller_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'category_id' => $category->id,
            'name' => 'Roti Tawar',
            'price' => 15000,
            'stock' => 5,
            'image' => UploadedFile::fake()->image('roti.jpg'),
        ])->assertRedirect(route('seller.products.index'));

        $product = Product::where('name', 'Roti Tawar')->first();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_uploaded_product_image_is_optimized_to_webp(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'category_id' => $category->id,
            'name' => 'Keripik Singkong',
            'price' => 12000,
            'stock' => 9,
            'image' => UploadedFile::fake()->image('keripik.png'),
        ])->assertRedirect(route('seller.products.index'));

        $product = Product::where('name', 'Keripik Singkong')->first();
        $this->assertNotNull($product->image);
        $this->assertStringEndsWith('.webp', $product->image);
        Storage::disk('public')->assertExists($product->image);
        $this->assertSame('image/webp', getimagesize(Storage::disk('public')->path($product->image))['mime']);
    }

    public function test_seller_can_set_a_discount_percent(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);

        $this->actingAs($seller)->post(route('seller.products.store'), [
            'category_id' => $category->id,
            'name' => 'Minyak Goreng',
            'price' => 35000,
            'discount_percent' => 20,
            'stock' => 10,
        ])->assertRedirect(route('seller.products.index'));

        $product = Product::where('name', 'Minyak Goreng')->first();
        $this->assertSame(20, $product->discount_percent);
        $this->assertSame(28000, $product->effectivePrice());
    }
}
