<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_cart_api(): void
    {
        $this->getJson('/api/cart')->assertStatus(401)->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/cart/items', ['product_id' => 1])->assertStatus(401);
        $this->putJson('/api/cart/items/1')->assertStatus(401);
        $this->deleteJson('/api/cart/items/1')->assertStatus(401);
    }

    public function test_buyer_can_add_products_to_the_cart_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertCreated()
            ->assertJsonPath('message', 'Produk ditambahkan ke keranjang.');

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_the_same_product_again_increments_its_quantity(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000, stock: 10);

        $this->actingAs($buyer, 'sanctum')->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2])->assertCreated();
        $this->actingAs($buyer, 'sanctum')->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 3])->assertCreated();

        $this->assertSame(5, CartItem::query()->where('user_id', $buyer->id)->value('quantity'));
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_adding_more_than_available_stock_is_rejected(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000, stock: 3);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cart']);
    }

    public function test_buyer_can_list_the_cart_with_totals_via_api(): void
    {
        $buyer = User::factory()->create();
        $firstProduct = $this->product('Beras', 76000);
        $secondProduct = $this->product('Minyak', 35000);
        $this->cart($buyer, [$firstProduct->id => 2, $secondProduct->id => 1]);

        $response = $this->actingAs($buyer, 'sanctum')->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'product', 'quantity', 'note', 'line_total']],
                'meta' => ['count', 'subtotal'],
            ])
            ->assertJsonPath('data.0.quantity', 2)
            ->assertJsonPath('data.0.line_total', 152000)
            ->assertJsonPath('data.0.product.id', $firstProduct->id)
            ->assertJsonPath('meta.count', 3)
            ->assertJsonPath('meta.subtotal', 187000);
    }

    public function test_buyer_can_update_quantity_and_note_of_a_cart_item_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 1]);

        $item = CartItem::query()->where('user_id', $buyer->id)->first();

        $this->actingAs($buyer, 'sanctum')
            ->putJson('/api/cart/items/'.$item->id, ['quantity' => 5, 'note' => 'Bungkus rapi'])
            ->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'id' => $item->id,
            'quantity' => 5,
            'note' => 'Bungkus rapi',
        ]);
    }

    public function test_buyer_can_remove_a_cart_item_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($buyer, [$product->id => 2]);

        $item = CartItem::query()->where('user_id', $buyer->id)->first();

        $this->actingAs($buyer, 'sanctum')
            ->deleteJson('/api/cart/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('message', 'Produk dihapus dari keranjang.');

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_cart_rejects_an_inactive_product(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $product->update(['status' => 'inactive']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertNotFound();
    }

    public function test_user_cannot_modify_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = $this->product('Beras', 76000);
        $this->cart($owner, [$product->id => 1]);

        $item = CartItem::query()->where('user_id', $owner->id)->first();

        $this->actingAs($intruder, 'sanctum')
            ->putJson('/api/cart/items/'.$item->id, ['quantity' => 3])
            ->assertForbidden();

        $this->assertSame(1, CartItem::query()->value('quantity'));
    }

    public function test_seller_cannot_use_the_cart_api(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product('Beras', 76000);

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertForbidden();
    }

    private function product(string $name, int $price, int $stock = 10): Product
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
            'stock' => $stock,
        ]);
    }
}
