<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-public');
    }

    public function test_product_listing_is_public_and_paginated(): void
    {
        $store = $this->store('Toko TerAspal', 'open');
        $category = Category::create(['name' => 'Sembako']);

        foreach (range(1, 20) as $index) {
            Product::create([
                'seller_profile_id' => $store->id,
                'category_id' => $category->id,
                'name' => "Produk {$index}",
                'price' => 1000 * $index,
                'stock' => 10,
            ]);
        }

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonFragment(['id' => 20, 'name' => 'Produk 20'])
            ->assertJsonMissing(['id' => 5, 'name' => 'Produk 5'])
            ->assertJsonStructure([
                'data' => [['id', 'name', 'price', 'effective_price', 'stock', 'status', 'image_url', 'category', 'store', 'rating', 'created_at']],
            ]);
    }

    public function test_product_listing_can_be_filtered_searched_and_sorted(): void
    {
        $storeA = $this->store('Toko A', 'open');
        $storeB = $this->store('Toko B', 'open');
        $sembako = Category::create(['name' => 'Sembako']);
        $sayur = Category::create(['name' => 'Sayur']);

        Product::create(['seller_profile_id' => $storeA->id, 'category_id' => $sembako->id, 'name' => 'Beras Premium', 'price' => 50000, 'stock' => 5]);
        Product::create(['seller_profile_id' => $storeB->id, 'category_id' => $sayur->id, 'name' => 'Tomat Segar', 'price' => 10000, 'stock' => 0]);
        Product::create(['seller_profile_id' => $storeA->id, 'category_id' => $sembako->id, 'name' => 'Minyak Goreng', 'price' => 20000, 'stock' => 3]);

        $this->getJson('/api/products?q=beras')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Beras Premium');

        $this->getJson('/api/products?category='.$sayur->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Tomat Segar');

        $this->getJson('/api/products?store='.$storeA->id)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/products?in_stock=1&sort=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Minyak Goreng');

        $this->getJson('/api/products?min_price=20000&max_price=50000')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/products?sort=price_desc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Beras Premium');
    }

    public function test_product_listing_hides_inactive_products_and_closed_stores(): void
    {
        $openStore = $this->store('Toko Buka', 'open');
        $closedStore = $this->store('Toko Tutup', 'closed');
        $category = Category::create(['name' => 'Sembako']);

        Product::create(['seller_profile_id' => $openStore->id, 'category_id' => $category->id, 'name' => 'Produk Aktif', 'price' => 10000, 'stock' => 5]);
        Product::create(['seller_profile_id' => $openStore->id, 'category_id' => $category->id, 'name' => 'Produk Nonaktif', 'price' => 10000, 'stock' => 5, 'status' => 'inactive']);
        Product::create(['seller_profile_id' => $closedStore->id, 'category_id' => $category->id, 'name' => 'Produk Toko Tutup', 'price' => 10000, 'stock' => 5]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Produk Aktif');
    }

    public function test_product_listing_rejects_invalid_filters(): void
    {
        $this->getJson('/api/products?sort=ngawur')->assertStatus(422)->assertJsonValidationErrors(['sort']);
        $this->getJson('/api/products?per_page=500')->assertStatus(422)->assertJsonValidationErrors(['per_page']);
        $this->getJson('/api/products?category=99999')->assertStatus(422)->assertJsonValidationErrors(['category']);
    }

    public function test_product_detail_includes_store_shipping_and_rating(): void
    {
        $store = $this->store('Toko Segar', 'open');
        $store->update([
            'enable_delivery' => true,
            'enable_pickup' => true,
            'delivery_fee' => 5000,
            'min_order_amount' => 20000,
            'free_shipping_threshold' => 100000,
        ]);
        $category = Category::create(['name' => 'Sayur']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Bayam Segar', 'price' => 8000, 'discount_percent' => 20, 'stock' => 10]);
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-API-001', 'buyer_id' => $buyer->id, 'subtotal' => 8000, 'total_amount' => 8000]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => 'Bayam Segar', 'price' => 8000, 'quantity' => 1, 'subtotal' => 8000]);
        Review::create(['buyer_id' => $buyer->id, 'order_id' => $order->id, 'product_id' => $product->id, 'seller_profile_id' => $store->id, 'rating' => 5, 'review' => 'Segar.']);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Bayam Segar')
            ->assertJsonPath('data.price', 8000)
            ->assertJsonPath('data.effective_price', 6400)
            ->assertJsonPath('data.category.name', 'Sayur')
            ->assertJsonPath('data.store.store_name', 'Toko Segar')
            ->assertJsonPath('data.store.delivery_fee', 5000)
            ->assertJsonPath('data.rating.count', 1);
    }

    public function test_product_detail_returns_404_for_hidden_products(): void
    {
        $store = $this->store('Toko A', 'open');
        $closedStore = $this->store('Toko B', 'closed');
        $category = Category::create(['name' => 'Sembako']);
        $inactive = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Nonaktif', 'price' => 1000, 'stock' => 1, 'status' => 'inactive']);
        $hidden = Product::create(['seller_profile_id' => $closedStore->id, 'category_id' => $category->id, 'name' => 'Tersembunyi', 'price' => 1000, 'stock' => 1]);

        $this->getJson("/api/products/{$inactive->id}")->assertNotFound();
        $this->getJson("/api/products/{$hidden->id}")->assertNotFound();
    }

    public function test_categories_endpoint_lists_active_categories_with_product_counts(): void
    {
        $store = $this->store('Toko A', 'open');
        $active = Category::create(['name' => 'Sembako']);
        $inactive = Category::create(['name' => 'Arsip', 'status' => 'inactive']);
        Product::create(['seller_profile_id' => $store->id, 'category_id' => $active->id, 'name' => 'Beras', 'price' => 1000, 'stock' => 1]);
        Product::create(['seller_profile_id' => $store->id, 'category_id' => $inactive->id, 'name' => 'Lama', 'price' => 1000, 'stock' => 1, 'status' => 'inactive']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sembako')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_stores_endpoints_only_expose_open_stores(): void
    {
        $open = $this->store('Toko Terbuka', 'open');
        $closed = $this->store('Toko Tertutup', 'closed');
        $empty = $this->store('Toko Kosong', 'open');
        $category = Category::create(['name' => 'Sembako']);
        Product::create(['seller_profile_id' => $open->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 1000, 'stock' => 1]);

        $this->getJson('/api/stores')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.store_name', 'Toko Terbuka')
            ->assertJsonPath('data.0.products_count', 1);

        $this->getJson('/api/stores?q=terbuka')->assertOk()->assertJsonCount(1, 'data');

        $this->getJson("/api/stores/{$open->id}")
            ->assertOk()
            ->assertJsonPath('data.store_name', 'Toko Terbuka')
            ->assertJsonStructure(['data' => ['id', 'store_name', 'business_hours', 'is_open_now', 'shipping', 'rating']]);

        $this->getJson("/api/stores/{$closed->id}")->assertNotFound();
        $this->getJson("/api/stores/{$empty->id}")->assertOk();
    }

    public function test_public_endpoints_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 120; $attempt++) {
            $this->getJson('/api/products')->assertOk();
        }

        $this->getJson('/api/products')->assertStatus(429);
    }

    private function store(string $name, string $status): SellerProfile
    {
        $seller = User::factory()->create(['role' => 'seller']);

        return SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => $name,
            'phone' => $seller->phone,
            'address' => 'Blok Z1',
            'status' => $status,
        ]);
    }
}
