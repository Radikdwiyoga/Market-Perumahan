<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_report_shows_totals_and_breakdowns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 10]);
        $order = Order::create(['order_number' => 'ORD-RPT-001', 'buyer_id' => $buyer->id, 'subtotal' => 100000, 'total_amount' => 100000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'shipping_method' => 'seller_delivery',
        ]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => 'Beras', 'price' => 50000, 'quantity' => 2, 'subtotal' => 100000]);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 100000, 'status' => 'paid']);

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Rp100.000')
            ->assertSee('Toko Warga')
            ->assertSee('Sembako')
            ->assertSee('Transfer bank')
            ->assertSee('Diantar seller');
    }

    public function test_admin_report_supports_custom_period_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['period' => 'custom', 'start_date' => '2020-01-01', 'end_date' => '2020-01-02']))
            ->assertOk()
            ->assertSee('Rp0');
    }

    public function test_admin_report_csv_export_downloads_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition', ''));
    }

    public function test_admin_report_renders_payment_method_chart(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Bintang', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-RPT-CHART', 'buyer_id' => $buyer->id, 'subtotal' => 100000, 'total_amount' => 100000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'shipping_method' => 'seller_delivery',
        ]);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 100000, 'status' => 'paid']);

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Grafik metode pembayaran')
            ->assertSee('width: 100%');
    }

    public function test_buyer_cannot_access_admin_report(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.reports.index'))->assertForbidden();
    }
}
