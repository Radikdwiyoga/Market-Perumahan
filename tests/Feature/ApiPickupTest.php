<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiPickupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_pickup_api(): void
    {
        $this->getJson('/api/seller-orders/1/pickup')->assertStatus(401);
        $this->postJson('/api/seller-orders/1/pickup/verify')->assertStatus(401);
    }

    public function test_seller_can_view_their_pickup_order_information(): void
    {
        $seller = $this->seller('Warung Warga');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-001', $buyer->id);
        $sellerOrder = $this->pickupOrder($order, $seller, '482915');

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller-orders/'.$sellerOrder->id.'/pickup')
            ->assertOk()
            ->assertJsonPath('order_number', 'ORD-PICKUP-001')
            ->assertJsonPath('pickup_code', '482915')
            ->assertJsonPath('store.store_name', 'Warung Warga')
            ->assertJsonPath('buyer.name', $buyer->name)
            ->assertJsonPath('ready', false);
    }

    public function test_seller_cannot_view_another_stores_pickup_order(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-002', $buyer->id);
        $sellerOrder = $this->pickupOrder($order, $otherSeller, '111111');

        $this->actingAs($seller, 'sanctum')->getJson('/api/seller-orders/'.$sellerOrder->id.'/pickup')->assertNotFound();
    }

    public function test_pickup_info_rejects_a_delivery_order(): void
    {
        $seller = $this->seller('Warung Warga');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-003', $buyer->id);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);

        $this->actingAs($seller, 'sanctum')->getJson('/api/seller-orders/'.$sellerOrder->id.'/pickup')->assertStatus(422);
    }

    public function test_seller_can_verify_the_pickup_code_and_complete_the_order(): void
    {
        $seller = $this->seller('Warung Warga');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-004', $buyer->id);
        $sellerOrder = $this->pickupOrder($order, $seller, '482915', shippingStatus: 'ready');
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'method' => 'cod', 'amount' => 10000]);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller-orders/'.$sellerOrder->id.'/pickup/verify', ['pickup_code' => '482915'])
            ->assertOk()
            ->assertJsonPath('message', 'Kode valid, pesanan berhasil diambil.');

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'completed', 'status' => 'completed', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_completed']);
    }

    public function test_seller_cannot_verify_with_a_wrong_pickup_code(): void
    {
        $seller = $this->seller('Warung Warga');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-005', $buyer->id);
        $sellerOrder = $this->pickupOrder($order, $seller, '482915', shippingStatus: 'ready');

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller-orders/'.$sellerOrder->id.'/pickup/verify', ['pickup_code' => '000000'])
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'ready']);
    }

    public function test_pickup_cannot_be_verified_before_the_order_is_ready(): void
    {
        $seller = $this->seller('Warung Warga');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-PICKUP-006', $buyer->id);
        $sellerOrder = $this->pickupOrder($order, $seller, '482915');

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller-orders/'.$sellerOrder->id.'/pickup/verify', ['pickup_code' => '482915'])
            ->assertStatus(422);
    }

    private function seller(string $storeName): User
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => $storeName, 'phone' => $seller->phone, 'address' => 'Blok A1']);

        return $seller;
    }

    private function order(string $number, int $buyerId): Order
    {
        return Order::create(['order_number' => $number, 'buyer_id' => $buyerId, 'subtotal' => 10000, 'shipping_fee' => 0, 'total_amount' => 10000]);
    }

    private function pickupOrder(Order $order, User $seller, string $code, string $shippingStatus = 'pending'): SellerOrder
    {
        return SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $seller->sellerProfile()->first()->id,
            'subtotal' => $order->subtotal,
            'total_amount' => $order->total_amount,
            'shipping_method' => 'store_pickup',
            'shipping_status' => $shippingStatus,
            'pickup_code' => $code,
        ]);
    }
}
