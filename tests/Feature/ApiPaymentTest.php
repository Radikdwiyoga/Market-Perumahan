<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_payment_api(): void
    {
        $this->postJson('/api/orders/1/payment', [])->assertStatus(401);
        $this->postJson('/api/payments/1/proof')->assertStatus(401);
        $this->getJson('/api/payments/1')->assertStatus(401);
        $this->postJson('/api/payments/1/verify')->assertStatus(401);
        $this->postJson('/api/payments/1/reject', ['rejection_reason' => 'x'])->assertStatus(401);
    }

    public function test_buyer_can_pay_with_cod_via_api(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-001', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => Payment::METHOD_COD])
            ->assertCreated()
            ->assertJsonPath('data.method', Payment::METHOD_COD)
            ->assertJsonPath('data.amount', $sellerOrder->total_amount)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.requires_proof', false);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'seller_order_id' => $sellerOrder->id,
            'buyer_id' => $buyer->id,
            'method' => Payment::METHOD_COD,
            'status' => 'pending',
        ]);
    }

    public function test_buyer_can_select_bank_transfer_and_upload_proof(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-002', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => Payment::METHOD_BANK_TRANSFER])
            ->assertCreated()
            ->assertJsonPath('data.method', Payment::METHOD_BANK_TRANSFER)
            ->assertJsonPath('data.requires_proof', true);

        $paymentId = $response->json('data.id');

        $this->actingAs($buyer, 'sanctum')
            ->post('/api/payments/'.$paymentId.'/proof', ['proof_image' => UploadedFile::fake()->image('proof.jpg')])
            ->assertOk()
            ->assertJsonPath('data.id', $paymentId);

        $payment = Payment::findOrFail($paymentId);
        $this->assertNotNull($payment->proof_image);
        Storage::disk('public')->assertExists($payment->proof_image);
    }

    public function test_buyer_can_select_qris_and_its_image_is_snapshotted(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        SellerPaymentSetting::create([
            'seller_profile_id' => $store->sellerProfile()->firstOrFail()->id,
            'qris_image' => 'qris/store.jpg',
            'qris_status' => 'active',
        ]);
        $order = $this->order('ORD-PAY-QRIS', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => Payment::METHOD_QRIS])
            ->assertCreated()
            ->assertJsonPath('data.method', Payment::METHOD_QRIS)
            ->assertJsonPath('data.qris_image_snapshot_url', Storage::disk('public')->url('qris/store.jpg'));
    }

    public function test_payment_api_rejects_unknown_methods(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-003', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        foreach (['gateway', 'invalid'] as $method) {
            $this->actingAs($buyer, 'sanctum')
                ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => $method])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['method']);
        }
    }

    public function test_payment_api_rejects_qris_when_the_store_has_not_uploaded_one(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-NOQRIS', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => Payment::METHOD_QRIS])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Toko ini belum mengunggah QRIS.');
    }

    public function test_buyer_cannot_create_a_payment_for_another_users_order(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-004', $otherBuyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', ['seller_order_id' => $sellerOrder->id, 'method' => Payment::METHOD_COD])
            ->assertForbidden();
    }

    public function test_payment_detail_can_be_viewed_by_its_buyer_and_its_seller(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-005', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD);

        $this->actingAs($buyer, 'sanctum')->getJson('/api/payments/'.$payment->id)->assertOk()->assertJsonPath('data.id', $payment->id);
        $this->actingAs($seller, 'sanctum')->getJson('/api/payments/'.$payment->id)->assertOk()->assertJsonPath('data.order_number', 'ORD-PAY-005');
    }

    public function test_payment_detail_is_forbidden_for_other_users(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $otherSeller = $this->seller('Toko Lain');
        $order = $this->order('ORD-PAY-006', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD);

        $this->actingAs($otherBuyer, 'sanctum')->getJson('/api/payments/'.$payment->id)->assertForbidden();
        $this->actingAs($otherSeller, 'sanctum')->getJson('/api/payments/'.$payment->id)->assertNotFound();
    }

    public function test_seller_can_verify_a_cod_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-007', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD);

        $this->actingAs($seller, 'sanctum')->postJson('/api/payments/'.$payment->id.'/verify')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'paid', 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'payment_verified']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_VERIFIED']);
    }

    public function test_seller_can_verify_a_bank_transfer_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-008', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_BANK_TRANSFER);

        $this->actingAs($seller, 'sanctum')->postJson('/api/payments/'.$payment->id.'/verify')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    public function test_seller_cannot_verify_an_already_paid_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-008B', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD, 'paid');

        $this->actingAs($seller, 'sanctum')->postJson('/api/payments/'.$payment->id.'/verify')->assertStatus(422);
    }

    public function test_seller_cannot_verify_another_stores_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Toko A');
        $otherSeller = $this->seller('Toko B');
        $order = $this->order('ORD-PAY-009', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $otherSeller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD);

        $this->actingAs($seller, 'sanctum')->postJson('/api/payments/'.$payment->id.'/verify')->assertNotFound();
    }

    public function test_seller_can_reject_a_cod_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-010', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $seller, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/payments/'.$payment->id.'/reject', ['rejection_reason' => 'Uang tidak sesuai'])
            ->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed', 'rejection_reason' => 'Uang tidak sesuai']);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'failed']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'payment_rejected']);
    }

    public function test_buyer_cannot_restart_a_settled_payment(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-011', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_BANK_TRANSFER, Payment::STATUS_PAID);
        $sellerOrder->update(['payment_status' => Payment::STATUS_PAID]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', [
                'seller_order_id' => $sellerOrder->id,
                'method' => Payment::METHOD_COD,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pembayaran untuk sub-order ini sudah lunas.');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'method' => Payment::METHOD_BANK_TRANSFER,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'paid']);
    }

    public function test_seller_cannot_reject_an_already_paid_payment(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-012', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_COD, Payment::STATUS_PAID);
        $sellerOrder->update(['payment_status' => Payment::STATUS_PAID]);

        $this->actingAs($store, 'sanctum')
            ->postJson('/api/payments/'.$payment->id.'/reject', ['rejection_reason' => 'Uang tidak masuk'])
            ->assertStatus(422);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid', 'rejection_reason' => null]);
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'payment_status' => 'paid']);
    }

    public function test_seller_cannot_verify_a_payment_that_was_already_rejected(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-013', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');
        $payment = $this->payment($order, $sellerOrder, $buyer, Payment::METHOD_BANK_TRANSFER, Payment::STATUS_FAILED);

        $this->actingAs($store, 'sanctum')->postJson('/api/payments/'.$payment->id.'/verify')->assertStatus(422);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
    }

    public function test_restarting_a_payment_resets_the_expiry_window(): void
    {
        $buyer = User::factory()->create();
        $store = $this->seller('Warung Warga');
        $order = $this->order('ORD-PAY-014', $buyer->id);
        $sellerOrder = $this->sellerOrder($order, $store, 'seller_delivery');
        $sellerOrder->update(['payment_status' => Payment::STATUS_FAILED, 'payment_due_at' => now()->subMinute()]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/payment', [
                'seller_order_id' => $sellerOrder->id,
                'method' => Payment::METHOD_BANK_TRANSFER,
            ])
            ->assertCreated();

        $this->assertTrue($sellerOrder->refresh()->payment_due_at->isFuture());
        $this->assertDatabaseHas('seller_orders', ['id' => $sellerOrder->id, 'status' => 'pending', 'payment_status' => 'pending']);
    }

    public function test_checkout_via_api_can_use_bank_transfer(): void
    {
        $buyer = User::factory()->create();
        $seller = $this->seller('Warung Warga');
        $store = $seller->sellerProfile()->firstOrFail();

        Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => Category::query()->firstOrCreate(['name' => 'Sembako'], ['slug' => 'sembako', 'status' => 'active'])->id,
            'name' => 'Kopi Bubuk 250g',
            'slug' => 'kopi-bubuk-250g',
            'price' => 40000,
            'stock' => 10,
            'status' => 'active',
        ]);
        $product = Product::query()->firstOrFail();
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => [$store->id => 'store_pickup'],
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
            ])
            ->assertCreated()
            ->assertJsonPath('data.total_amount', 40000);

        $this->assertDatabaseHas('payments', ['method' => Payment::METHOD_BANK_TRANSFER]);
    }

    public function test_seller_can_manage_payment_settings_via_api(): void
    {
        Storage::fake('public');
        $seller = $this->seller('Warung Warga');
        $store = $seller->sellerProfile()->firstOrFail();

        $this->actingAs($seller, 'sanctum')
            ->putJson('/api/seller/payment-setting', [
                'bank_name' => 'BRI',
                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Budi',
            ])
            ->assertOk()
            ->assertJsonPath('data.bank_name', 'BRI');

        $this->actingAs($seller, 'sanctum')
            ->post('/api/seller/payment-setting/qris', ['qris_image' => UploadedFile::fake()->image('qris.png')])
            ->assertSuccessful()
            ->assertJsonPath('data.qris_status', 'active');

        $setting = SellerPaymentSetting::where('seller_profile_id', $store->id)->firstOrFail();
        Storage::disk('public')->assertExists($setting->qris_image);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/seller/payment-setting')
            ->assertOk()
            ->assertJsonPath('data.qris_status', 'active');

        $this->actingAs($seller, 'sanctum')
            ->deleteJson('/api/seller/payment-setting/qris')
            ->assertOk();

        $this->assertNull($setting->refresh()->qris_image);
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

    /**
     * Sub-order beserta ledger pembayarannya, sama seperti hasil OrderService.
     */
    private function sellerOrder(Order $order, User $seller, string $method, string $paymentMethod = Payment::METHOD_COD): SellerOrder
    {
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $seller->sellerProfile()->firstOrFail()->id,
            'subtotal' => $order->subtotal,
            'total_amount' => $order->total_amount,
            'shipping_method' => $method,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'seller_order_id' => $sellerOrder->id,
            'buyer_id' => $order->buyer_id,
            'seller_profile_id' => $sellerOrder->seller_profile_id,
            'method' => $paymentMethod,
            'amount' => $sellerOrder->total_amount,
        ]);

        return $sellerOrder;
    }

    /**
     * Setel ulang ledger pembayaran yang dibuat {@see ApiPaymentTest::sellerOrder()}.
     */
    private function payment(Order $order, SellerOrder $sellerOrder, User $buyer, string $method, string $status = 'pending'): Payment
    {
        $payment = $sellerOrder->payments()->firstOrFail();
        $payment->update([
            'method' => $method,
            'amount' => $sellerOrder->total_amount,
            'status' => $status,
        ]);

        return $payment->refresh();
    }
}
