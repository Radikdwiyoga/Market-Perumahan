<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_dashboard_shows_sales_and_order_status_counts(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['name' => 'Siti Aminah']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);

        $this->sellerOrder($buyer, $store, 'ORD-DASH-001', ['status' => 'pending', 'shipping_status' => 'pending', 'total_amount' => 12000]);
        $this->sellerOrder($buyer, $store, 'ORD-DASH-002', ['status' => 'processing', 'payment_status' => 'paid', 'shipping_status' => 'ready', 'total_amount' => 33000]);
        $this->sellerOrder($buyer, $store, 'ORD-DASH-003', ['status' => 'completed', 'payment_status' => 'paid', 'shipping_status' => 'completed', 'total_amount' => 50000, 'created_at' => now()->subDays(2)]);
        $this->sellerOrder($buyer, $store, 'ORD-DASH-004', [
            'status' => 'pending',
            'payment_status' => 'paid',
            'shipping_method' => 'store_pickup',
            'shipping_status' => 'ready',
            'total_amount' => 7000,
        ]);

        $response = $this->actingAs($seller)->get(route('seller.dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Toko')
            ->assertSee('Warung Warga')
            ->assertSee('Penjualan hari ini')
            ->assertSee('Rp40.000')
            ->assertSee('Siti Aminah')
            ->assertSee('ORD-DASH-003');
    }

    public function test_seller_dashboard_lists_low_stock_products(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Sembako']);
        Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras Hampir Habis', 'price' => 50000, 'stock' => 2]);
        Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Gula Stok Aman', 'price' => 15000, 'stock' => 20]);

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertSee('Beras Hampir Habis')
            ->assertSee('Sisa 2')
            ->assertDontSee('Gula Stok Aman');
    }

    public function test_low_stock_threshold_is_bound_as_a_value_not_a_column(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);

        DB::enableQueryLog();
        $this->actingAs($seller)->get(route('seller.dashboard'))->assertOk();
        $sql = implode("\n", array_column(DB::getQueryLog(), 'query'));
        DB::flushQueryLog();

        // whereColumn() memperlakukan argumen ketiga sebagai nama kolom, sehingga
        // menghasilkan `"stock" <= "5"` yang ditolak PostgreSQL (SQLite memakainya
        // sebagai string literal sehingga bug ini lolos di test).
        $this->assertStringContainsString('"stock" <= ?', $sql);
        $this->assertStringNotContainsString('"5"', $sql);
    }

    public function test_seller_dashboard_excludes_another_stores_orders(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Sendiri', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko Tetangga', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $this->sellerOrder($buyer, $otherStore, 'ORD-DASH-OTHER', ['status' => 'pending']);

        $this->actingAs($seller)
            ->get(route('seller.dashboard'))
            ->assertOk()
            ->assertDontSee('ORD-DASH-OTHER');
    }

    public function test_buyer_cannot_open_the_seller_dashboard(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('seller.dashboard'))->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function sellerOrder(User $buyer, SellerProfile $store, string $orderNumber, array $attributes = []): SellerOrder
    {
        $total = $attributes['total_amount'] ?? 10000;
        $order = Order::create([
            'order_number' => $orderNumber,
            'buyer_id' => $buyer->id,
            'subtotal' => $total,
            'total_amount' => $total,
        ]);

        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => $total,
            'total_amount' => $total,
            'shipping_method' => 'seller_delivery',
            ...$attributes,
        ]);

        if (isset($attributes['created_at'])) {
            $sellerOrder->forceFill(['created_at' => $attributes['created_at']])->save();
        }

        return $sellerOrder->refresh();
    }
}
