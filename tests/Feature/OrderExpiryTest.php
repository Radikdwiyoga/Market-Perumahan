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

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_unpaid_order_is_cancelled_and_stock_restored(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 5]);
        $order = Order::create(['order_number' => 'ORD-EXPIRED-001', 'buyer_id' => $buyer->id, 'subtotal' => 100000, 'total_amount' => 100000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'shipping_method' => 'seller_delivery',
            'payment_due_at' => now()->subMinutes(16),
        ]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => 'Beras', 'price' => 50000, 'quantity' => 2, 'subtotal' => 100000]);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 100000]);
        $product->decrement('stock', 2);

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'cancelled', 'payment_status' => 'failed']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    public function test_expiring_one_sub_order_does_not_restore_another_stores_stock(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $category = Category::create(['name' => 'Sembako']);
        $expiredProduct = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 5]);
        $keptProduct = Product::create(['seller_profile_id' => $otherStore->id, 'category_id' => $category->id, 'name' => 'Minyak', 'price' => 30000, 'stock' => 8]);

        $order = Order::create(['order_number' => 'ORD-EXPIRED-003', 'buyer_id' => $buyer->id, 'subtotal' => 80000, 'total_amount' => 80000]);
        $expired = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'shipping_method' => 'seller_delivery',
            'payment_due_at' => now()->subMinutes(16),
        ]);
        SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $otherStore->id,
            'subtotal' => 30000,
            'total_amount' => 30000,
            'shipping_method' => 'seller_delivery',
            'payment_due_at' => now()->addMinutes(10),
        ]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $expiredProduct->id, 'product_name' => 'Beras', 'price' => 50000, 'quantity' => 1, 'subtotal' => 50000]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'product_id' => $keptProduct->id, 'product_name' => 'Minyak', 'price' => 30000, 'quantity' => 1, 'subtotal' => 30000]);
        $expiredProduct->decrement('stock');
        $keptProduct->decrement('stock');

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertSame(5, $expiredProduct->refresh()->stock);
        $this->assertSame(7, $keptProduct->refresh()->stock);
        $this->assertSame('cancelled', $expired->refresh()->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_two_expired_sub_orders_from_different_stores_restore_stock_once_each(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $category = Category::create(['name' => 'Sembako']);
        $productA = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 10]);
        $productB = Product::create(['seller_profile_id' => $otherStore->id, 'category_id' => $category->id, 'name' => 'Minyak', 'price' => 30000, 'stock' => 10]);
        $order = Order::create(['order_number' => 'ORD-EXPIRED-004', 'buyer_id' => $buyer->id, 'subtotal' => 80000, 'total_amount' => 80000]);

        foreach ([[$store, $productA, 50000], [$otherStore, $productB, 30000]] as [$sellerStore, $product, $amount]) {
            SellerOrder::create([
                'order_id' => $order->id,
                'seller_profile_id' => $sellerStore->id,
                'subtotal' => $amount,
                'total_amount' => $amount,
                'shipping_method' => 'seller_delivery',
                'payment_due_at' => now()->subMinutes(16),
            ]);
            OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $sellerStore->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => $amount, 'quantity' => 1, 'subtotal' => $amount]);
            $product->decrement('stock');
        }

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertSame(10, $productA->refresh()->stock);
        $this->assertSame(10, $productB->refresh()->stock);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_order_with_mixed_terminal_sub_orders_is_marked_completed(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $order = Order::create(['order_number' => 'ORD-MIXED-001', 'buyer_id' => $buyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);

        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery', 'status' => 'completed']);
        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery', 'status' => 'cancelled']);

        $order->refreshStatus();

        $this->assertSame('completed', $order->refresh()->status);
    }

    public function test_order_is_still_processing_when_one_sub_order_is_completed_and_another_pending(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $order = Order::create(['order_number' => 'ORD-MIXED-002', 'buyer_id' => $buyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);

        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery', 'status' => 'completed']);
        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery', 'status' => 'pending']);

        $order->refreshStatus();

        $this->assertSame('processing', $order->refresh()->status);
    }

    public function test_order_within_payment_window_is_left_untouched(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-EXPIRED-002', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'seller_delivery',
            'payment_due_at' => now()->addMinutes(10),
        ]);

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'pending']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }
}
