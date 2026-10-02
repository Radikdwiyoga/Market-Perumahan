<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_save_bank_details_and_qris(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);

        $response = $this->actingAs($seller)->put(route('seller.payment.update'), [
            'bank_name' => 'Bank ABC',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Warung Warga',
            'qris_image' => UploadedFile::fake()->image('qris.png'),
        ]);

        $response->assertRedirect(route('seller.payment.edit'));
        $setting = SellerPaymentSetting::first();
        $this->assertSame('active', $setting->qris_status);
        Storage::disk('public')->assertExists($setting->qris_image);
    }

    public function test_seller_can_remove_qris(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        SellerPaymentSetting::create([
            'seller_profile_id' => $store->id,
            'qris_image' => 'qris/example.png',
            'qris_status' => 'active',
            'qris_uploaded_at' => now(),
        ]);

        $this->actingAs($seller)->delete(route('seller.payment.qris.destroy'))->assertRedirect();

        $setting = SellerPaymentSetting::first();
        $this->assertNull($setting->qris_image);
        $this->assertSame('inactive', $setting->qris_status);
    }

    public function test_buyer_can_upload_payment_proof(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $order = Order::create(['order_number' => 'ORD-TEST-001', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 10000]);

        $response = $this->actingAs($buyer)->post(route('payments.proof.store', $payment), ['proof_image' => UploadedFile::fake()->image('proof.jpg')]);

        $response->assertRedirect();
        $payment->refresh();
        $this->assertNotNull($payment->proof_image);
        Storage::disk('public')->assertExists($payment->proof_image);
    }

    public function test_buyer_cannot_upload_proof_to_another_buyers_payment(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $order = Order::create(['order_number' => 'ORD-TEST-002', 'buyer_id' => $otherBuyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $otherBuyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 10000]);

        $this->actingAs($buyer)->post(route('payments.proof.store', $payment), ['proof_image' => UploadedFile::fake()->image('proof.jpg')])->assertForbidden();
    }

    public function test_buyer_cannot_upload_proof_for_cod(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $order = Order::create(['order_number' => 'ORD-TEST-003', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 10000]);

        $this->actingAs($buyer)->post(route('payments.proof.store', $payment), ['proof_image' => UploadedFile::fake()->image('proof.jpg')])->assertStatus(422);
    }

    public function test_seller_can_verify_a_bank_transfer_payment(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $order = Order::create(['order_number' => 'ORD-TEST-004', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000]);
        $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 10000, 'total_amount' => 10000, 'shipping_method' => 'seller_delivery']);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'bank_transfer', 'amount' => 10000]);

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertRedirect();

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('paid', $payment->sellerOrder->payment_status);
    }
}
