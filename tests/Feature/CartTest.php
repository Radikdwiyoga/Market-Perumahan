<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_add_and_update_a_product_in_the_cart(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product(stock: 5);

        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($buyer)->put(route('cart.update', $product), ['quantity' => 4])->assertRedirect();

        $this->assertSame(4, CartItem::query()->where('user_id', $buyer->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_cart_page_exposes_live_price_targets_for_each_item(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 2]);

        $response = $this->actingAs($buyer)->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('data-cart-line', false)
            ->assertSee('data-unit-price="76000"', false)
            ->assertSee('data-cart-quantity', false)
            ->assertSee('data-cart-line-total', false)
            ->assertSee('data-cart-summary', false)
            ->assertSee('Rp152.000');
    }

    public function test_buyer_can_add_a_note_per_cart_item_and_it_is_removed_with_the_item(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->actingAs($buyer)->put(route('cart.update', $product), [
            'quantity' => 2,
            'note' => 'Potong tipis',
        ])->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'note' => 'Potong tipis',
        ]);

        $this->actingAs($buyer)->delete(route('cart.destroy', $product))->assertRedirect();

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_buyer_can_remove_a_product_from_the_cart(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer)->post(route('cart.store', $product));
        $this->actingAs($buyer)->delete(route('cart.destroy', $product))->assertRedirect();

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_buyer_cannot_add_more_than_available_stock(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product(stock: 3);

        $this->actingAs($buyer)->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect();

        $this->actingAs($buyer)
            ->post(route('cart.store', $product), ['quantity' => 2])
            ->assertRedirect()
            ->assertSessionHasErrors('cart');

        $this->assertSame(2, CartItem::query()->value('quantity'));
    }

    public function test_seller_cannot_use_the_buyer_cart(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)->get(route('cart.index'))->assertForbidden();
    }

    private function product(int $stock = 10): Product
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);
        $category = Category::create(['name' => fake()->unique()->word()]);

        return Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Beras Pulen',
            'price' => 76000,
            'stock' => $stock,
        ]);
    }
}
