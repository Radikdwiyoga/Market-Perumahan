<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_checkout_products_from_multiple_sellers(): void
    {
        $buyer = User::factory()->create();
        $firstProduct = $this->product('Beras', 76000);
        $secondProduct = $this->product('Minyak', 35000);

        $this->cart($buyer, [$firstProduct->id => 2, $secondProduct->id => 1]);

        $response = $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [
                    $firstProduct->seller_profile_id => 'seller_delivery',
                    $secondProduct->seller_profile_id => 'store_pickup',
                ],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ]);

        $order = Order::first();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertNotNull($order);
        $this->assertSame(2, SellerOrder::where('order_id', $order->id)->count());
        $this->assertDatabaseHas('products', ['id' => $firstProduct->id, 'stock' => 8]);
        $this->assertDatabaseHas('products', ['id' => $secondProduct->id, 'stock' => 9]);
        $this->assertSame(0, CartItem::query()->where('user_id', $buyer->id)->count());
    }

    public function test_seller_cannot_checkout(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)->get(route('checkout.create'))->assertForbidden();
    }

    public function test_checkout_offers_bank_transfer_cod_and_qris_when_store_has_qris(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        SellerPaymentSetting::create([
            'seller_profile_id' => $product->seller_profile_id,
            'bank_name' => 'BRI',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Budi',
            'qris_image' => 'qris/example.jpg',
            'qris_status' => 'active',
        ]);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('Transfer bank')
            ->assertSee('QRIS')
            ->assertSee('COD')
            ->assertSee('aria-label="Transfer bank"', false)
            ->assertSee('data-bank-logo', false)
            ->assertSee('data-copy-text="1234567890"', false)
            ->assertSee('aria-label="Salin nomor rekening BRI"', false)
            ->assertSee('data-copy-status', false);
    }

    public function test_checkout_disables_qris_when_a_store_has_not_uploaded_one(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('QRIS belum tersedia');
    }

    public function test_checkout_page_lists_shipping_options_per_store(): void
    {
        $buyer = User::factory()->create();
        $firstProduct = $this->product('Beras', 50000);
        $secondProduct = $this->product('Minyak', 30000);
        $firstProduct->sellerProfile->update(['delivery_fee' => 5000]);
        $secondProduct->sellerProfile->update(['enable_pickup' => false, 'delivery_fee' => 7000]);

        $this->cart($buyer, [$firstProduct->id => 1, $secondProduct->id => 1]);

        $response = $this->actingAs($buyer)
            ->get(route('checkout.create'));

        $response->assertOk()
            ->assertSee('Ongkir Rp5.000')
            ->assertSee('Ongkir Rp7.000')
            ->assertSee('Ambil di toko')
            ->assertSee('Ongkos kirim')
            ->assertSee('12.000')
            ->assertSee('92.000');
    }

    public function test_checkout_rejects_an_unknown_payment_method(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->from(route('checkout.create'))
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'gateway',
            ])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_checkout_rejects_qris_when_the_store_has_not_uploaded_one(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'qris',
            ])
            ->assertStatus(422)
            ->assertSee('belum mengunggah QRIS');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_bank_transfer_checkout_sets_a_payment_due_date_and_snapshots_nothing(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Nasi Goreng', 20000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => Payment::METHOD_BANK_TRANSFER,
            ])
            ->assertRedirect();

        $payment = Payment::firstOrFail();
        $this->assertSame(Payment::METHOD_BANK_TRANSFER, $payment->method);
        $this->assertNull($payment->qris_image_snapshot);
        $this->assertNotNull(SellerOrder::firstOrFail()->payment_due_at);
    }

    public function test_qris_checkout_snapshots_the_store_qris_image(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Nasi Goreng', 20000);
        SellerPaymentSetting::create([
            'seller_profile_id' => $product->seller_profile_id,
            'qris_image' => 'qris/store-qris.jpg',
            'qris_status' => 'active',
        ]);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => Payment::METHOD_QRIS,
            ])
            ->assertRedirect();

        $this->assertSame('qris/store-qris.jpg', Payment::firstOrFail()->qris_image_snapshot);
    }

    public function test_cod_checkout_has_no_payment_due_date(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Es Teh', 6000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertNull(SellerOrder::first()->payment_due_at);
    }

    public function test_checkout_uses_discounted_price_for_totals_and_order_items(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras Premium', 100000, 20);

        $this->cart($buyer, [$product->id => 2]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $order = Order::first();
        $item = $order->items()->first();

        $this->assertSame(160000, $order->total_amount);
        $this->assertSame(80000, $item->price);
        $this->assertSame(160000, $item->subtotal);
        $this->assertSame(160000, SellerOrder::first()->total_amount);
    }

    public function test_checkout_carries_per_item_note_into_the_order_item(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);

        $this->cart($buyer, [$product->id => 2], [$product->id => 'Potong tipis, jangan plastik']);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'note' => 'Potong tipis, jangan plastik',
        ]);
        $this->assertSame(0, CartItem::query()->where('user_id', $buyer->id)->count());
    }

    public function test_checkout_applies_shipping_fee_per_seller(): void
    {
        $buyer = User::factory()->create();
        $firstProduct = $this->product('Beras', 50000);
        $secondProduct = $this->product('Minyak', 30000);
        $firstProduct->sellerProfile->update(['delivery_fee' => 5000]);
        $secondProduct->sellerProfile->update(['delivery_fee' => 7000]);

        $this->cart($buyer, [$firstProduct->id => 1, $secondProduct->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [
                    $firstProduct->seller_profile_id => 'seller_delivery',
                    $secondProduct->seller_profile_id => 'seller_delivery',
                ],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $order = Order::first();

        $this->assertSame(12000, $order->shipping_fee);
        $this->assertSame(92000, $order->total_amount);
        $this->assertSame(55000, SellerOrder::where('seller_profile_id', $firstProduct->seller_profile_id)->first()->total_amount);
        $this->assertSame(37000, SellerOrder::where('seller_profile_id', $secondProduct->seller_profile_id)->first()->total_amount);
    }

    public function test_checkout_gives_free_shipping_above_the_threshold(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 120000);
        $product->sellerProfile->update(['delivery_fee' => 5000, 'free_shipping_threshold' => 100000]);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertSame(0, Order::first()->shipping_fee);
    }

    public function test_delivery_requires_the_minimum_order_amount(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 10000);
        $product->sellerProfile->update(['min_order_amount' => 25000]);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertSee('Minimal pembelian');
    }

    public function test_checkout_rejects_a_disabled_shipping_method(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);
        $product->sellerProfile->update(['enable_pickup' => false]);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertSee('tidak tersedia');
    }

    public function test_delivery_checkout_requires_a_shipping_address(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => '',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422)
            ->assertSee('Alamat pengiriman wajib diisi');
    }

    public function test_pickup_uses_the_store_address_as_pickup_location(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);
        $product->sellerProfile->update(['address' => 'Blok A1 No. 10']);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'store_pickup'],
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shipments', ['address' => 'Blok A1 No. 10']);
    }

    private function product(string $name, int $price, ?int $discountPercent = null): Product
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
            'discount_percent' => $discountPercent,
            'stock' => 10,
        ]);
    }
}
