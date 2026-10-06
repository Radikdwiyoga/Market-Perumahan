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

class SellerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_verify_a_cod_payment(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-VERIFY-001', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 10000]);

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'paid', 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_seller_cannot_verify_another_stores_payment(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $order = Order::create(['order_number' => 'ORD-VERIFY-002', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $otherStore->id, 'method' => 'cod', 'amount' => 10000]);

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertNotFound();
    }

    public function test_seller_delivers_then_buyer_confirms_receipt(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Paket Sembako', 'price' => 30000, 'stock' => 1]);
        $order = Order::create(['order_number' => 'ORD-DETAIL-001', 'buyer_id' => $buyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 30000, 'total_amount' => 30000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => Payment::METHOD_COD, 'amount' => 30000]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => 'Paket Sembako', 'price' => 30000, 'quantity' => 1, 'subtotal' => 30000]);

        $this->actingAs($seller)->get(route('seller.orders.index'))->assertOk()->assertSee('ORD-DETAIL-001')->assertSee('Diantar oleh seller')->assertSee('Status pengiriman: Pending');
        $this->actingAs($seller)->get(route('seller.orders.show', $sellerOrder))->assertOk()->assertSee('Paket Sembako');
        $this->actingAs($seller)->patch(route('seller.orders.shipping.update', $sellerOrder), ['shipping_status' => 'delivered'])->assertRedirect(route('seller.orders.show', $sellerOrder));
        $this->actingAs($buyer)->post(route('orders.confirm', $sellerOrder))->assertRedirect();

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'delivered', 'status' => 'completed']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_seller_cannot_advance_unpaid_bank_transfer_to_shipping(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-UNPAID-DELIVERY', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery']);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => Payment::METHOD_BANK_TRANSFER, 'amount' => 20000]);

        $this->actingAs($seller)
            ->patch(route('seller.orders.shipping.update', $sellerOrder), ['shipping_status' => 'ready'])
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'pending', 'shipping_status' => 'pending', 'payment_status' => 'pending']);
    }

    public function test_buyer_cannot_confirm_delivery_while_bank_transfer_is_unpaid(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-UNPAID-CONFIRM', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 20000, 'total_amount' => 20000, 'shipping_method' => 'seller_delivery', 'shipping_status' => 'delivered']);
        Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => Payment::METHOD_BANK_TRANSFER, 'amount' => 20000]);

        $this->actingAs($buyer)->post(route('orders.confirm', $sellerOrder))->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'pending', 'payment_status' => 'pending']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_seller_can_verify_pickup_code_and_complete_pickup_order(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-PICKUP-001', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'store_pickup',
            'shipping_status' => 'ready',
            'pickup_code' => '482915',
        ]);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 20000]);

        $this->actingAs($seller)->post(route('seller.orders.pickup', $sellerOrder), ['pickup_code' => '482915'])->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'completed', 'status' => 'completed', 'payment_status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_seller_cannot_verify_a_payment_that_was_already_rejected(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-VERIFY-003', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery', 'payment_status' => 'failed']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 10000, 'status' => 'failed', 'rejection_reason' => 'Dana tidak masuk.']);

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertStatus(422);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'failed', 'status' => 'pending']);
    }

    public function test_seller_cannot_verify_an_already_paid_payment(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-VERIFY-004', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery', 'payment_status' => 'paid']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 10000, 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertStatus(422);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    public function test_pickup_cannot_be_completed_before_bank_transfer_is_paid(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-PICKUP-003', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'store_pickup',
            'shipping_status' => 'ready',
            'pickup_code' => '482915',
        ]);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 20000, 'status' => 'pending']);

        $this->actingAs($seller)
            ->post(route('seller.orders.pickup', $sellerOrder), ['pickup_code' => '482915'])
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'pending', 'shipping_status' => 'ready', 'payment_status' => 'pending']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_pickup_verification_is_rejected_for_a_cancelled_order(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-PICKUP-004', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'store_pickup',
            'shipping_status' => 'ready',
            'status' => 'cancelled',
            'payment_status' => 'failed',
            'pickup_code' => '482915',
        ]);

        $this->actingAs($seller)->post(route('seller.orders.pickup', $sellerOrder), ['pickup_code' => '482915'])->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'cancelled', 'shipping_status' => 'ready']);
    }

    public function test_seller_cannot_verify_with_wrong_pickup_code(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-PICKUP-002', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'store_pickup',
            'shipping_status' => 'ready',
            'pickup_code' => '482915',
        ]);

        $this->actingAs($seller)->post(route('seller.orders.pickup', $sellerOrder), ['pickup_code' => '000000'])->assertStatus(422);

        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'shipping_status' => 'ready', 'status' => 'pending']);
    }

    public function test_pickup_delivery_method_cannot_be_marked_delivered(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-PICKUP-003', 'buyer_id' => $buyer->id, 'subtotal' => 20000, 'total_amount' => 20000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 20000,
            'total_amount' => 20000,
            'shipping_method' => 'store_pickup',
        ]);

        $this->actingAs($seller)->patch(route('seller.orders.shipping.update', $sellerOrder), ['shipping_status' => 'delivered'])->assertSessionHasErrors('shipping_status');
    }

    public function test_seller_cannot_update_another_stores_shipping_status(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $order = Order::create(['order_number' => 'ORD-DETAIL-002', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);

        $this->actingAs($seller)->patch(route('seller.orders.shipping.update', $sellerOrder), ['shipping_status' => 'completed'])->assertNotFound();
    }

    public function test_seller_cannot_open_another_stores_order_detail(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $order = Order::create(['order_number' => 'ORD-DETAIL-003', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);

        $this->actingAs($seller)->get(route('seller.orders.show', $sellerOrder))->assertNotFound();
    }

    public function test_seller_order_detail_only_lists_the_stores_own_items(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $otherStore = SellerProfile::create(['user_id' => $otherSeller->id, 'store_name' => 'Toko B', 'phone' => $otherSeller->phone, 'address' => 'B1']);
        $category = Category::create(['name' => 'Sembako']);
        $ownProduct = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras Sendiri', 'price' => 10000, 'stock' => 5]);
        $otherProduct = Product::create(['seller_profile_id' => $otherStore->id, 'category_id' => $category->id, 'name' => 'Minyak Tetangga', 'price' => 20000, 'stock' => 5]);
        $order = Order::create(['order_number' => 'ORD-DETAIL-004', 'buyer_id' => $buyer->id, 'subtotal' => 30000, 'total_amount' => 30000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $ownProduct->id, 'product_name' => 'Beras Sendiri', 'price' => 10000, 'quantity' => 1, 'subtotal' => 10000]);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $otherStore->id, 'product_id' => $otherProduct->id, 'product_name' => 'Minyak Tetangga', 'price' => 20000, 'quantity' => 1, 'subtotal' => 20000]);

        $this->actingAs($seller)
            ->get(route('seller.orders.show', $sellerOrder))
            ->assertOk()
            ->assertSee('Beras Sendiri')
            ->assertDontSee('Minyak Tetangga');
    }

    public function test_seller_can_search_and_filter_their_orders(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['name' => 'Siti Aminah']);
        $otherBuyer = User::factory()->create(['name' => 'Budi Santoso']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $pendingOrder = Order::create(['order_number' => 'ORD-SEARCH-001', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        SellerOrder::create(['order_id' => $pendingOrder->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $doneOrder = Order::create(['order_number' => 'ORD-SEARCH-002', 'buyer_id' => $otherBuyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        SellerOrder::create(['order_id' => $doneOrder->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery', 'status' => 'completed']);

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['q' => 'Siti']))
            ->assertOk()
            ->assertSee('ORD-SEARCH-001')
            ->assertDontSee('ORD-SEARCH-002');

        $this->actingAs($seller)
            ->get(route('seller.orders.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('ORD-SEARCH-002')
            ->assertDontSee('ORD-SEARCH-001');
    }

    public function test_buyer_cannot_open_the_seller_order_detail(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko A', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => 'ORD-DETAIL-005', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);

        $this->actingAs($buyer)->get(route('seller.orders.show', $sellerOrder))->assertForbidden();
    }
}
