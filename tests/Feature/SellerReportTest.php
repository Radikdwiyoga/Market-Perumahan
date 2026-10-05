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

class SellerReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_report_shows_revenue_and_top_products(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Saya', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Makanan']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 10]);
        $order = Order::create(['order_number' => 'ORD-SRPT-001', 'buyer_id' => $buyer->id, 'subtotal' => 100000, 'total_amount' => 100000]);
        SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'shipping_method' => 'store_pickup',
        ]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => 'Beras', 'price' => 50000, 'quantity' => 2, 'subtotal' => 100000]);

        $this->actingAs($seller)->get(route('seller.reports.index'))
            ->assertOk()
            ->assertSee('Toko Saya')
            ->assertSee('Penjualan hari ini')
            ->assertSee('Rp100.000')
            ->assertSee('Beras');
    }

    public function test_seller_report_only_counts_owned_orders(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Saya', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko Lain', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $category = Category::create(['name' => 'Makanan']);
        $order = Order::create(['order_number' => 'ORD-SRPT-002', 'buyer_id' => $buyer->id, 'subtotal' => 50000, 'total_amount' => 50000]);
        SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $otherStore->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'shipping_method' => 'seller_delivery',
        ]);
        Product::create(['seller_profile_id' => $otherStore->id, 'category_id' => $category->id, 'name' => 'Produk Lain', 'price' => 50000, 'stock' => 10]);

        $this->actingAs($seller)->get(route('seller.reports.index'))
            ->assertOk()
            ->assertSee('Rp0')
            ->assertDontSee('Toko Lain');
    }

    public function test_seller_report_shows_payment_method_breakdown_and_chart(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Saya', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-SRPT-PAY', 'buyer_id' => $buyer->id, 'subtotal' => 150000, 'total_amount' => 150000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 150000,
            'total_amount' => 150000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'shipping_method' => 'seller_delivery',
        ]);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 100000, 'status' => 'paid']);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 50000, 'status' => 'paid']);

        $this->actingAs($seller)->get(route('seller.reports.index'))
            ->assertOk()
            ->assertSee('Statistik pembayaran')
            ->assertSee('Transfer bank')
            ->assertSee('COD')
            ->assertSee('67%')
            ->assertSee('33%')
            ->assertSee('width: 67%');
    }

    public function test_seller_report_csv_export_downloads_file(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Saya', 'phone' => $seller->phone, 'address' => 'A1']);

        $response = $this->actingAs($seller)->get(route('seller.reports.export'));

        $response->assertOk();
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition', ''));
        $this->assertStringContainsString('Metode Pembayaran', $response->streamedContent());
    }

    public function test_seller_report_csv_neutralises_formula_injection(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Formula', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Makanan']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 10]);
        $order = Order::create(['order_number' => 'ORD-SRPT-CSV-001', 'buyer_id' => $buyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 30000,
            'total_amount' => 30000,
            'shipping_method' => 'seller_delivery',
            'payment_status' => Payment::STATUS_PAID,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'product_id' => $product->id,
            'product_name' => '=HYPERLINK("http://penyerang.test","Klik")',
            'price' => 30000,
            'quantity' => 1,
            'subtotal' => 30000,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'seller_order_id' => $sellerOrder->id,
            'buyer_id' => $buyer->id,
            'seller_profile_id' => $store->id,
            'method' => Payment::METHOD_COD,
            'amount' => 30000,
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $csv = $this->actingAs($seller)->get(route('seller.reports.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
        $this->assertStringNotContainsString("\n=HYPERLINK", $csv);
    }

    public function test_buyer_cannot_access_seller_report(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('seller.reports.index'))->assertForbidden();
    }
}
