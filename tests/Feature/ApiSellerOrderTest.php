<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiSellerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_seller_order_api(): void
    {
        $this->getJson('/api/seller/orders')->assertStatus(401);
        $this->getJson('/api/seller/orders/1')->assertStatus(401);
        $this->postJson('/api/seller/orders/1/accept')->assertStatus(401);
        $this->postJson('/api/seller/orders/1/process')->assertStatus(401);
        $this->postJson('/api/seller/orders/1/ready')->assertStatus(401);
        $this->postJson('/api/seller/orders/1/deliver')->assertStatus(401);
        $this->postJson('/api/seller/orders/1/complete')->assertStatus(401);
    }

    public function test_buyer_cannot_list_seller_orders(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer, 'sanctum')->getJson('/api/seller/orders')->assertForbidden();
    }

    public function test_seller_can_list_and_filter_their_orders(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $buyer = User::factory()->create(['name' => 'Siti Aminah']);
        $otherBuyer = User::factory()->create(['name' => 'Budi Santoso']);

        $pendingOrder = $this->order('ORD-API-001', $buyer->id);
        $this->sellerOrder($pendingOrder, $seller, 'seller_delivery');
        $doneOrder = $this->order('ORD-API-002', $otherBuyer->id);
        $this->sellerOrder($doneOrder, $seller, 'seller_delivery', status: 'completed');
        $foreignOrder = $this->order('ORD-API-003', $buyer->id);
        $this->sellerOrder($foreignOrder, $otherSeller, 'seller_delivery');

        $response = $this->actingAs($seller, 'sanctum')->getJson('/api/seller/orders');

        $response->assertOk()->assertJsonCount(2, 'data');
        $numbers = collect($response->json('data'))->pluck('order_number')->all();
        $this->assertContains('ORD-API-001', $numbers);
        $this->assertContains('ORD-API-002', $numbers);
        $this->assertNotContains('ORD-API-003', $numbers);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller/orders?status=completed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-API-002');

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller/orders?q=Siti')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-API-001');
    }

    public function test_seller_can_view_their_order_detail_with_items(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $seller->sellerProfile()->first()->id, 'category_id' => $category->id, 'name' => 'Paket Sembako', 'price' => 30000, 'stock' => 1]);
        $order = $this->order('ORD-API-DETAIL', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $seller->sellerProfile()->first()->id, 'product_id' => $product->id, 'product_name' => 'Paket Sembako', 'price' => 30000, 'quantity' => 1, 'subtotal' => 30000]);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller/orders/'.$sellerOrder->id)
            ->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-API-DETAIL')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'Paket Sembako');
    }

    public function test_seller_cannot_view_another_stores_order_detail(): void
    {
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-004', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $otherSeller, 'seller_delivery');

        $this->actingAs($seller, 'sanctum')->getJson('/api/seller/orders/'.$sellerOrder->id)->assertNotFound();
    }

    public function test_seller_can_accept_a_pending_order(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-ACCEPT', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller/orders/'.$sellerOrder->id.'/accept')->assertOk();

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_processing']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SELLER_ORDER_ACCEPTED']);
    }

    public function test_seller_cannot_accept_an_already_processing_order(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-ACCEPT-2', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery', status: 'processing');

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller/orders/'.$sellerOrder->id.'/accept')->assertStatus(422);
    }

    public function test_seller_cannot_accept_or_process_an_unpaid_bank_transfer_order(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-UNPAID-ACCEPT', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $sellerOrder->update(['payment_status' => Payment::STATUS_PENDING]);
        $sellerOrder->payments()->update(['method' => Payment::METHOD_BANK_TRANSFER, 'status' => Payment::STATUS_PENDING]);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/orders/'.$sellerOrder->id.'/accept')
            ->assertStatus(422);

        $sellerOrder->update(['status' => 'processing']);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller/orders/'.$sellerOrder->id.'/process')
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'processing', 'payment_status' => Payment::STATUS_PENDING]);
    }

    public function test_seller_can_transition_shipping_through_the_full_delivery_flow(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-FLOW', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $shipment = Shipment::create(['seller_order_id' => $sellerOrder->id, 'method' => 'seller_delivery', 'address' => 'Jl. Mawar 1', 'recipient_name' => $buyer->name, 'recipient_phone' => $buyer->phone, 'shipping_fee' => 0, 'status' => 'pending']);

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller-orders/'.$sellerOrder->id.'/ready')->assertOk();
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'ready']);
        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'ready']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_ready']);

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller-orders/'.$sellerOrder->id.'/out-for-delivery')->assertOk();
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'out_for_delivery']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_out_for_delivery']);

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller-orders/'.$sellerOrder->id.'/delivered')->assertOk();
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'delivered']);
        $this->assertDatabaseHas('shipments', ['id' => $shipment->id, 'status' => 'delivered']);
        $this->assertNotNull(Shipment::find($shipment->id)->delivered_at);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_delivered']);
    }

    public function test_seller_cannot_advance_unpaid_bank_transfer_to_shipping_via_api(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-UNPAID', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $sellerOrder->update(['payment_status' => Payment::STATUS_PENDING]);
        $sellerOrder->payments()->update(['method' => Payment::METHOD_BANK_TRANSFER, 'status' => Payment::STATUS_PENDING]);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller-orders/'.$sellerOrder->id.'/ready')
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'pending', 'status' => 'pending']);
    }

    public function test_seller_cannot_complete_unpaid_bank_transfer_pickup_via_api(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-PICKUP-UNPAID', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'store_pickup', shippingStatus: 'ready');
        $sellerOrder->payments()->update(['method' => Payment::METHOD_BANK_TRANSFER, 'status' => Payment::STATUS_PENDING]);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/seller-orders/'.$sellerOrder->id.'/pickup/verify', ['pickup_code' => '123456'])
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'ready', 'status' => 'pending']);
    }

    public function test_pickup_order_cannot_be_marked_out_for_delivery(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-PICKUP-1', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'store_pickup');

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller-orders/'.$sellerOrder->id.'/out-for-delivery')->assertStatus(422);
    }

    public function test_seller_can_complete_a_delivered_delivery_order(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-COMPLETE', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery', shippingStatus: 'delivered');

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller/orders/'.$sellerOrder->id.'/complete')->assertOk();

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'completed']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('payments', ['seller_order_id' => $sellerOrder->id, 'status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_completed']);
    }

    public function test_seller_cannot_complete_a_pending_order(): void
    {
        $seller = $this->seller('Toko A');
        $buyer = User::factory()->create();
        $order = $this->order('ORD-API-PENDING', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');

        $this->actingAs($seller, 'sanctum')->postJson('/api/seller/orders/'.$sellerOrder->id.'/complete')->assertStatus(422);
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

    private function sellerOrder(Order $order, User $seller, string $method, string $status = 'pending', string $shippingStatus = 'pending'): SellerOrder
    {
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $seller->sellerProfile()->first()->id,
            'subtotal' => $order->subtotal,
            'total_amount' => $order->total_amount,
            'shipping_method' => $method,
            'shipping_status' => $shippingStatus,
            'status' => $status,
            'pickup_code' => $method === 'store_pickup' ? '123456' : null,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'seller_order_id' => $sellerOrder->id,
            'buyer_id' => $order->buyer_id,
            'seller_profile_id' => $sellerOrder->seller_profile_id,
            'method' => Payment::METHOD_COD,
            'amount' => $sellerOrder->total_amount,
        ]);

        return $sellerOrder;
    }
}
