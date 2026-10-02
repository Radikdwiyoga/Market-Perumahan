<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_shipping_api(): void
    {
        $this->getJson('/api/orders/1/shipping')->assertStatus(401);
        $this->postJson('/api/orders/1/shipping')->assertStatus(401);
    }

    public function test_buyer_can_view_the_shipping_summary_of_their_order(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = Order::create(['order_number' => 'ORD-SHIP-001', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'shipping_fee' => 0, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        Shipment::create(['seller_order_id' => $sellerOrder->id, 'method' => 'seller_delivery', 'address' => 'Jl. Mawar No.1', 'recipient_name' => $buyer->name, 'recipient_phone' => $buyer->phone, 'shipping_fee' => 0, 'status' => 'pending']);

        $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/orders/'.$order->id.'/shipping')
            ->assertOk()
            ->assertJsonPath('order_number', 'ORD-SHIP-001')
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.shipping_method', 'seller_delivery')
            ->assertJsonPath('orders.0.shipment.address', 'Jl. Mawar No.1')
            ->assertJsonPath('orders.0.shipment.recipient_name', $buyer->name);
    }

    public function test_buyer_cannot_view_another_users_shipping(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = Order::create(['order_number' => 'ORD-SHIP-002', 'buyer_id' => $otherBuyer->id, 'subtotal' => 10000, 'shipping_fee' => 0, 'total_amount' => 10000]);

        $this->actingAs($buyer, 'sanctum')->getJson('/api/orders/'.$order->id.'/shipping')->assertForbidden();
    }

    public function test_buyer_can_update_the_delivery_address_of_a_pending_order(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = Order::create(['order_number' => 'ORD-SHIP-003', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'shipping_fee' => 0, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $shipment = Shipment::create(['seller_order_id' => $sellerOrder->id, 'method' => 'seller_delivery', 'address' => 'Jl. Lama No.1', 'recipient_name' => $buyer->name, 'recipient_phone' => $buyer->phone, 'shipping_fee' => 0, 'status' => 'pending']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/shipping', ['shipping_address' => 'Jl. Baru No.9'])
            ->assertOk()
            ->assertJsonPath('message', 'Alamat pengiriman berhasil diperbarui.');

        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'address' => 'Jl. Baru No.9']);
    }

    public function test_buyer_cannot_update_the_address_of_a_completed_order(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = Order::create(['order_number' => 'ORD-SHIP-004', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'shipping_fee' => 0, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        Shipment::create(['seller_order_id' => $sellerOrder->id, 'method' => 'seller_delivery', 'address' => 'Jl. Lama No.1', 'recipient_name' => $buyer->name, 'recipient_phone' => $buyer->phone, 'shipping_fee' => 0, 'status' => 'pending']);
        $order->update(['status' => 'completed']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/shipping', ['shipping_address' => 'Jl. Baru No.9'])
            ->assertStatus(422);

        $this->assertDatabaseHas('shipments', ['id' => $sellerOrder->shipment()->first()->id, 'address' => 'Jl. Lama No.1']);
    }

    private function seller(string $storeName): User
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => $storeName, 'phone' => $seller->phone, 'address' => 'Blok A1']);

        return $seller;
    }
}
