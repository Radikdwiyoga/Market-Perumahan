<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_displays_active_products_from_open_stores(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Minyak Goreng',
            'description' => 'Minyak goreng dua liter',
            'price' => 35000,
            'stock' => 5,
        ]);

        $response = $this->get('/');

        $response->assertOk()->assertSee('Minyak Goreng')->assertSee('Warung Warga');
    }

    public function test_landing_page_can_filter_products_by_search(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Makanan']);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Nasi Bakar',
            'price' => 18000,
            'stock' => 5,
        ]);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Sabun Cuci',
            'price' => 12000,
            'stock' => 5,
        ]);

        $response = $this->get('/?q=Nasi');

        $response->assertOk()->assertSee('Nasi Bakar')->assertDontSee('Sabun Cuci');
    }

    public function test_landing_page_shows_product_image_and_discounted_price(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'description' => 'Beras lima kilogram',
            'price' => 100000,
            'discount_percent' => 20,
            'stock' => 5,
            'image' => 'products/beras.png',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('products/beras.png')
            ->assertSee('-20%')
            ->assertSee('80.000')
            ->assertSee('100.000');
    }

    public function test_landing_page_lists_open_stores(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Toko_decimals',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'open_time' => '08:00',
            'close_time' => '21:00',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'price' => 100000,
            'stock' => 5,
        ]);

        $this->get('/')->assertOk()->assertSee('Toko_decimals')->assertSee('08:00 - 21:00');
    }

    public function test_product_page_shows_product_store_and_reviews(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1 No. 10',
            'open_time' => '08:00',
            'close_time' => '21:00',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'description' => 'Beras lima kilogram pilihan',
            'price' => 76000,
            'stock' => 12,
        ]);
        $buyer = User::factory()->create(['name' => 'Siti Aminah']);
        $order = Order::create([
            'order_number' => 'ORD-TEST-1',
            'buyer_id' => $buyer->id,
            'subtotal' => 76000,
            'shipping_fee' => 0,
            'total_amount' => 76000,
            'status' => 'completed',
        ]);
        Review::create([
            'order_id' => $order->id,
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
            'product_id' => $product->id,
            'rating' => 5,
            'review' => 'Pelayanan cepat dan barang sesuai.',
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk()
            ->assertSee('Beras Premium')
            ->assertSee('Beras lima kilogram pilihan')
            ->assertSee('76.000')
            ->assertSee('Stok 12')
            ->assertSee('Warung Warga')
            ->assertSee('Blok A1 No. 10')
            ->assertSee('08:00 - 21:00')
            ->assertSee('Pelayanan cepat dan barang sesuai.')
            ->assertSee('5,0');
    }

    public function test_product_page_returns_not_found_for_inactive_product_or_closed_store(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        $inactive = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Nonaktif',
            'price' => 1000,
            'stock' => 1,
            'status' => 'inactive',
        ]);

        $this->get(route('products.show', $inactive))->assertNotFound();

        $store->update(['status' => 'closed']);
        $active = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Produk Aktif',
            'price' => 1000,
            'stock' => 1,
        ]);

        $this->get(route('products.show', $active))->assertNotFound();
    }

    public function test_store_page_shows_active_products_and_business_hours(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1 No. 10',
            'description' => 'Kebutuhan harian warga',
            'open_time' => '08:00',
            'close_time' => '21:00',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'price' => 76000,
            'stock' => 4,
        ]);
        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Minyak Terbuang',
            'price' => 35000,
            'stock' => 2,
            'status' => 'inactive',
        ]);

        $response = $this->get(route('stores.show', $store));

        $response->assertOk()
            ->assertSee('Warung Warga')
            ->assertSee('Kebutuhan harian warga')
            ->assertSee('Blok A1 No. 10')
            ->assertSee('08:00 - 21:00')
            ->assertSee('Beras Premium')
            ->assertDontSee('Minyak Terbuang');
    }

    public function test_product_page_reflects_store_shipping_and_payment_settings(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Setengah',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'enable_pickup' => false,
            'delivery_fee' => 5000,
        ]);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'price' => 76000,
            'stock' => 4,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Diantar oleh penjual')
            ->assertSee('Ongkir Rp5.000 per pesanan')
            ->assertDontSee('Ambil sendiri di toko')
            ->assertDontSee('Transfer bank')
            ->assertDontSee('QRIS toko')
            ->assertSee('COD (bayar saat diterima)');
    }

    public function test_product_page_shows_the_store_bank_and_qris_when_configured(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Setengah', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        SellerPaymentSetting::create([
            'seller_profile_id' => $store->id,
            'bank_name' => 'BRI',
            'bank_account_number' => '1234567890',
            'qris_image' => 'qris/example.jpg',
            'qris_status' => 'active',
        ]);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras Premium', 'price' => 76000, 'stock' => 4]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Transfer bank (BRI)')
            ->assertSee('QRIS toko')
            ->assertSee('COD (bayar saat diterima)');
    }

    public function test_store_page_returns_not_found_for_closed_store(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Tutup',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'status' => 'closed',
        ]);

        $this->get(route('stores.show', $store))->assertNotFound();
    }
}
