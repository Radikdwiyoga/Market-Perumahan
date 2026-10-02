<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiFavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
    }

    public function test_guest_cannot_use_the_favorites_api(): void
    {
        $this->getJson('/api/favorites')->assertStatus(401)->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/favorites', ['product_id' => 1])->assertStatus(401);
        $this->deleteJson('/api/favorites/1')->assertStatus(401);
    }

    public function test_buyer_can_add_a_product_to_favorites_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/favorites', ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('message', 'Produk disimpan ke favorit.');

        $this->assertDatabaseHas('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_adding_the_same_product_again_results_in_one_favorite(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();

        $this->actingAs($buyer, 'sanctum')->postJson('/api/favorites', ['product_id' => $product->id])->assertCreated();
        $this->actingAs($buyer, 'sanctum')->postJson('/api/favorites', ['product_id' => $product->id])->assertCreated();

        $this->assertSame(1, Favorite::query()->count());
    }

    public function test_buyer_can_list_favorites_via_api(): void
    {
        $buyer = User::factory()->create();
        $firstProduct = $this->product('Beras Pulen');
        $secondProduct = $this->product('Minyak Goreng');
        Favorite::create(['user_id' => $buyer->id, 'product_id' => $firstProduct->id]);
        Favorite::create(['user_id' => $buyer->id, 'product_id' => $secondProduct->id]);

        $response = $this->actingAs($buyer, 'sanctum')->getJson('/api/favorites');

        $response->assertOk()->assertJsonCount(2, 'data');

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertCount(2, $ids);
        $this->assertContains($firstProduct->id, $ids);
        $this->assertContains($secondProduct->id, $ids);

        $response->assertJsonStructure([
            'data' => [['id', 'name', 'price', 'effective_price', 'stock', 'status', 'store']],
        ]);
    }

    public function test_buyer_can_remove_a_product_from_favorites_via_api(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        Favorite::create(['user_id' => $buyer->id, 'product_id' => $product->id]);

        $this->actingAs($buyer, 'sanctum')
            ->deleteJson('/api/favorites/'.$product->id)
            ->assertOk()
            ->assertJsonPath('message', 'Produk dihapus dari favorit.');

        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_removing_does_not_affect_another_users_favorite_via_api(): void
    {
        $buyer = User::factory()->create();
        $otherBuyer = User::factory()->create();
        $product = $this->product();
        Favorite::create(['user_id' => $otherBuyer->id, 'product_id' => $product->id]);

        $this->actingAs($buyer, 'sanctum')->deleteJson('/api/favorites/'.$product->id)->assertOk();

        $this->assertSame(1, Favorite::query()->count());
        $this->assertSame($otherBuyer->id, Favorite::query()->value('user_id'));
    }

    public function test_favorites_api_rejects_an_inactive_product(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        $product->update(['status' => 'inactive']);

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/favorites', ['product_id' => $product->id])
            ->assertNotFound();

        $this->assertSame(0, Favorite::query()->count());
    }

    public function test_favorites_api_requires_a_valid_product_id(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/favorites', ['product_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_seller_cannot_use_the_favorites_api(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->product();

        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/favorites', ['product_id' => $product->id])
            ->assertForbidden();
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
