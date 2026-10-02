<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_save_a_product_to_favorites(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer)->post(route('favorites.store', $product))->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_saving_the_same_product_again_keeps_a_single_favorite(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer)->post(route('favorites.store', $product))->assertRedirect();
        $this->actingAs($buyer)->post(route('favorites.store', $product))->assertRedirect();

        $this->assertSame(1, Favorite::query()->count());
    }

    public function test_buyer_can_see_only_their_own_favorites(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $favoriteProduct = $this->product('Beras Pulen');
        $otherProduct = $this->product('Minyak Goreng');
        Favorite::create(['user_id' => $buyer->id, 'product_id' => $favoriteProduct->id]);
        Favorite::create(['user_id' => $otherBuyer->id, 'product_id' => $otherProduct->id]);

        $response = $this->actingAs($buyer)->get(route('favorites.index'));

        $response->assertOk()
            ->assertSee($favoriteProduct->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_buyer_can_remove_a_product_from_favorites(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        Favorite::create(['user_id' => $buyer->id, 'product_id' => $product->id]);

        $this->actingAs($buyer)->delete(route('favorites.destroy', $product))->assertRedirect();

        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_removing_a_favorite_does_not_affect_another_users_favorite(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $product = $this->product();
        Favorite::create(['user_id' => $otherBuyer->id, 'product_id' => $product->id]);

        $this->actingAs($buyer)->delete(route('favorites.destroy', $product))->assertRedirect();

        $this->assertSame(1, Favorite::query()->count());
        $this->assertSame($otherBuyer->id, Favorite::query()->value('user_id'));
    }

    public function test_favorites_reject_an_inactive_product(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        $product->update(['status' => 'inactive']);

        $this->actingAs($buyer)->post(route('favorites.store', $product))->assertNotFound();

        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_seller_cannot_access_favorites(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)->get(route('favorites.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $product = $this->product();

        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->post(route('favorites.store', $product))->assertRedirect(route('login'));
    }

    private function product(string $name = 'Beras Pulen'): Product
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
            'name' => $name,
            'price' => 76000,
            'stock' => 10,
        ]);
    }
}
