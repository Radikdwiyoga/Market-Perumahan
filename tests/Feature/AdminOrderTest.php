<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_orders_and_verify_cod_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Admin', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-ADMIN-001', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 20000]);

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk()->assertSee('ORD-ADMIN-001')->assertSee('Toko Admin');
        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', $payment))->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_admin_can_reject_a_cod_payment_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Reject', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-ADMIN-002', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 20000]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.reject', $payment), ['rejection_reason' => 'Uang diterima kurang'])
            ->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed', 'rejection_reason' => 'Uang diterima kurang']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_admin_can_verify_a_bank_transfer_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Transfer', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-ADMIN-003', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 20000, 'proof_image' => 'payment-proofs/proof.jpg']);

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', $payment))->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_admin_cannot_verify_an_already_paid_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Lunas', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-ADMIN-004', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 20000, 'status' => 'paid']);

        $this->actingAs($admin)->patch(route('admin.orders.payments.verify', $payment))->assertStatus(422);
    }

    public function test_buyer_cannot_access_admin_orders(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.orders.index'))->assertForbidden();
    }
}
