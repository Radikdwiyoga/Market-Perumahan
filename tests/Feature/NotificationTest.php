<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_notifies_buyer_and_each_seller(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_created']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $product->sellerProfile->user_id, 'type' => 'new_order']);
    }

    public function test_seller_whatsapp_alert_links_to_the_orders_page(): void
    {
        config()->set('services.fonnte.enabled', true);
        config()->set('services.fonnte.token', 'test-token');
        Http::fake();

        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.fonnte.com/send'
                && str_contains($request['message'], route('seller.orders.index'));
        });
    }

    public function test_payment_verification_notifies_buyer(): void
    {
        [$seller, $buyer, $store, $order, $sellerOrder, $payment] = $this->orderFixture('ORD-NOTIF-001');

        $this->actingAs($seller)->patch(route('seller.orders.payments.verify', $payment))->assertRedirect();

        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'payment_verified']);
    }

    public function test_payment_rejection_notifies_buyer_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $buyer, , , , $payment] = $this->orderFixture('ORD-NOTIF-002');

        $this->actingAs($admin)
            ->patch(route('admin.orders.payments.reject', $payment), ['rejection_reason' => 'Bukti tidak jelas'])
            ->assertRedirect();

        $notification = UserNotification::query()->where('user_id', $buyer->id)->where('type', 'payment_rejected')->firstOrFail();
        $this->assertStringContainsString('Bukti tidak jelas', $notification->body);
    }

    public function test_expired_order_cancellation_notifies_buyer_and_seller(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Sembako']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Beras', 'price' => 50000, 'stock' => 5]);
        $order = Order::create(['order_number' => 'ORD-EXPIRED-N', 'buyer_id' => $buyer->id, 'subtotal' => 100000, 'total_amount' => 100000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 100000,
            'total_amount' => 100000,
            'shipping_method' => 'seller_delivery',
            'payment_due_at' => now()->subMinutes(16),
        ]);

        $this->artisan('orders:cancel-expired')->assertSuccessful();

        $this->assertDatabaseHas('user_notifications', ['user_id' => $buyer->id, 'type' => 'order_cancelled']);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $seller->id, 'type' => 'order_cancelled']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ORDER_CANCELLED']);
    }

    public function test_notifications_page_shows_only_the_users_own_notifications(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);

        UserNotification::create(['user_id' => $seller->id, 'title' => 'Order rahasia seller', 'body' => 'Pesanan baru.', 'type' => 'new_order']);
        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Pesanan saya', 'body' => 'Pesanan dibuat.', 'type' => 'order_created']);

        $this->actingAs($buyer)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pesanan saya')
            ->assertDontSee('Order rahasia seller');
    }

    public function test_user_can_mark_notification_as_read_and_mark_all_read(): void
    {
        $buyer = User::factory()->create();
        $notification = UserNotification::create(['user_id' => $buyer->id, 'title' => 'Test', 'body' => 'Isi notifikasi']);
        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Kedua', 'body' => 'Isi kedua']);

        $this->actingAs($buyer)->patch(route('notifications.read', $notification))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($buyer)->post(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, auth()->user()->userNotifications()->unread()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $notification = UserNotification::create(['user_id' => $seller->id, 'title' => 'Test', 'body' => 'Isi notifikasi']);

        $this->actingAs($buyer)->patch(route('notifications.read', $notification))->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * @return array{0: User, 1: User, 2: SellerProfile, 3: Order, 4: SellerOrder, 5: Payment}
     */
    private function orderFixture(string $orderNumber): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create();
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $order = Order::create(['order_number' => $orderNumber, 'buyer_id' => $buyer->id, 'subtotal' => 50000, 'total_amount' => 50000]);
        $sellerOrder = SellerOrder::create([
            'order_id' => $order->id,
            'seller_profile_id' => $store->id,
            'subtotal' => 50000,
            'total_amount' => 50000,
            'shipping_method' => 'seller_delivery',
        ]);
        $payment = Payment::create(['order_id' => $order->id, 'seller_order_id' => $sellerOrder->id, 'buyer_id' => $buyer->id, 'seller_profile_id' => $store->id, 'method' => 'cod', 'amount' => 50000]);

        return [$seller, $buyer, $store, $order, $sellerOrder, $payment];
    }

    private function product(string $name, int $price): Product
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => "Toko {$name}", 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $category = Category::create(['name' => $name]);

        return Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'stock' => 10,
        ]);
    }
}
