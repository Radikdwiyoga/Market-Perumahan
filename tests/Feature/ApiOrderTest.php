<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_order_api(): void
    {
        $this->getJson('/api/orders')->assertStatus(401)->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/orders', ['shipping_methods' => []])->assertStatus(401);
    }

    public function test_buyer_can_create_an_order_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 2]);

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ]);

        $order = Order::first();

        $response->assertCreated()
            ->assertJsonPath('data.order_number', $order->order_number)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure([
                'data' => ['id', 'order_number', 'subtotal', 'shipping_fee', 'total_amount', 'status', 'items', 'seller_orders'],
            ])
            ->assertJsonPath('data.items.0.product_name', 'Beras')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.seller_orders.0.pickup_code', null);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertSame(0, CartItem::query()->where('user_id', $buyer->id)->count());
    }

    public function test_order_creation_requires_shipping_methods_and_address(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => [],
                'shipping_address' => '',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_methods']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => '',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Alamat pengiriman wajib diisi untuk pesanan yang diantar.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_creation_with_an_empty_cart_is_rejected(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => ['1' => 'store_pickup'],
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Keranjang masih kosong.');
    }

    public function test_buyer_can_list_only_their_own_orders(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);
        $this->cart($otherBuyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => 'cod',
        ])->assertCreated();
        $this->actingAs($otherBuyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => 'cod',
        ])->assertCreated();

        $ownId = Order::query()->where('buyer_id', $buyer->id)->first()->id;
        $otherId = Order::query()->where('buyer_id', $otherBuyer->id)->first()->id;

        $response = $this->actingAs($buyer, 'sanctum')->getJson('/api/orders');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownId);

        $this->actingAs($buyer, 'sanctum')->getJson('/api/orders/'.$otherId)->assertForbidden();
    }

    public function test_buyer_can_view_an_order_detail_with_items_and_seller_orders(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
            'payment_method' => 'cod',
        ])->assertCreated();

        $order = Order::first();

        $response = $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/orders/'.$order->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.seller_orders.0.shipping_method', 'store_pickup')
            ->assertJsonPath('data.seller_orders.0.payment_status', 'pending');

        $this->assertNotNull($response->json('data.seller_orders.0.pickup_code'));
    }

    public function test_buyer_can_cancel_a_pending_order_restoring_stock(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 2]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => 'cod',
        ])->assertCreated();

        $order = Order::first();

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('message', 'Pesanan berhasil dibatalkan.');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 10]);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', SellerOrder::first()->fresh()->status);
    }

    public function test_cannot_cancel_an_order_that_has_been_processed(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => 'cod',
        ])->assertCreated();

        $order = Order::first();
        SellerOrder::first()->update(['status' => 'processing', 'shipping_status' => 'delivered']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/cancel')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan sudah diproses penjual dan tidak dapat dibatalkan.');
    }

    public function test_buyer_can_complete_a_delivered_order_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => 'cod',
        ])->assertCreated();

        $order = Order::first();
        SellerOrder::first()->update(['shipping_status' => 'delivered']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/complete')
            ->assertOk()
            ->assertJsonPath('message', 'Pesanan dikonfirmasi selesai.');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('completed', SellerOrder::first()->fresh()->status);
        $this->assertDatabaseHas('payments', ['seller_order_id' => SellerOrder::first()->id, 'status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('seller_orders', ['id' => SellerOrder::first()->id, 'payment_status' => Payment::STATUS_PAID]);
        $this->assertDatabaseHas('shipments', ['seller_order_id' => SellerOrder::first()->id, 'status' => 'completed']);
    }

    public function test_buyer_cannot_complete_a_delivered_bank_transfer_before_it_is_paid(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
            'shipping_address' => 'Blok A2 No. 15',
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
        ])->assertCreated();

        $order = Order::first();
        SellerOrder::first()->update(['shipping_status' => 'delivered']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/complete')
            ->assertStatus(422);

        $this->assertSame('pending', SellerOrder::first()->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseHas('payments', ['seller_order_id' => SellerOrder::first()->id, 'status' => Payment::STATUS_PENDING]);
    }

    public function test_complete_requires_a_delivered_delivery(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/orders', [
            'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
            'payment_method' => 'cod',
        ])->assertCreated();

        $order = Order::first();

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/orders/'.$order->id.'/complete')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Tidak ada pesanan yang bisa dikonfirmasi selesai.');
    }

    public function test_seller_cannot_create_orders_via_api(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product('Beras', 76000);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/orders', [
                'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
                'payment_method' => 'cod',
            ])
            ->assertForbidden();
    }

    private function product(string $name, int $price): Product
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => "Toko {$name}",
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
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
