<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_view_only_their_order_history(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-HISTORY-001', 'buyer_id' => $buyer->id, 'subtotal' => 25000, 'total_amount' => 25000]);
        $otherOrder = Order::create(['order_number' => 'ORD-HISTORY-002', 'buyer_id' => $otherBuyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);
        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 25000, 'total_amount' => 25000, 'shipping_method' => 'seller_delivery']);

        $response = $this->actingAs($buyer)->get(route('orders.index'));

        $response->assertOk()->assertSee('ORD-HISTORY-001')->assertDontSee('ORD-HISTORY-002')->assertSee('Warung Warga');
        $this->actingAs($buyer)->get(route('orders.show', $otherOrder))->assertForbidden();
    }

    public function test_seller_cannot_access_buyer_order_history(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)->get(route('orders.index'))->assertForbidden();
    }

    public function test_buyer_can_cancel_a_pending_order_and_stock_is_restored(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $product = $this->product($store);
        $order = $this->placeOrder($buyer, $store, $product, 2);

        $this->assertSame(8, $product->refresh()->stock);

        $this->actingAs($buyer)
            ->post(route('orders.cancel', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame('cancelled', $order->refresh()->status);
        $this->assertSame('cancelled', $order->sellerOrders()->first()->status);
        $this->assertSame('failed', $order->sellerOrders()->first()->payment_status);
        $this->assertSame(10, $product->refresh()->stock);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $seller->id,
            'type' => 'order_cancelled',
        ]);
    }

    public function test_buyer_cannot_cancel_an_order_that_is_already_processed(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $product = $this->product($store);
        $order = $this->placeOrder($buyer, $store, $product, 1);
        $order->sellerOrders()->update(['status' => 'processing']);

        $this->actingAs($buyer)->post(route('orders.cancel', $order))->assertStatus(422);

        $this->assertSame('pending', $order->refresh()->status);
        $this->assertSame(9, $product->refresh()->stock);
    }

    public function test_buyer_cannot_cancel_someone_elses_order(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $product = $this->product($store);
        $order = $this->placeOrder($otherBuyer, $store, $product, 1);

        $this->actingAs($buyer)->post(route('orders.cancel', $order))->assertForbidden();
    }

    private function product(SellerProfile $store): Product
    {
        $category = Category::create(['name' => 'Sembako']);

        return Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Premium',
            'price' => 5000,
            'stock' => 10,
        ]);
    }

    private function placeOrder(User $buyer, SellerProfile $store, Product $product, int $quantity): Order
    {
        $this->cart($buyer, [$product->id => $quantity]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$store->id => 'store_pickup'],
                'payment_method' => 'cod',
            ]);

        return Order::first();
    }
}
