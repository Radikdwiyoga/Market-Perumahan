<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_review_a_product_after_completed_order(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Review', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Review']);
        $product = Product::create(['seller_profile_id' => $store->id, 'category_id' => $category->id, 'name' => 'Produk Review', 'price' => 25000, 'stock' => 2]);
        $order = Order::create(['order_number' => 'ORD-REVIEW-001', 'buyer_id' => $buyer->id, 'subtotal' => 25000, 'total_amount' => 25000, 'status' => 'completed']);
        SellerOrder::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'subtotal' => 25000, 'total_amount' => 25000, 'shipping_method' => 'seller_delivery', 'shipping_status' => 'completed', 'status' => 'completed']);
        OrderItem::create(['order_id' => $order->id, 'seller_profile_id' => $store->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 25000, 'quantity' => 1, 'subtotal' => 25000]);

        $this->actingAs($buyer)->post(route('orders.reviews.store', $order), ['product_id' => $product->id, 'rating' => 5, 'review' => 'Sangat bagus'])->assertRedirect();

        $this->assertDatabaseHas('reviews', ['buyer_id' => $buyer->id, 'product_id' => $product->id, 'rating' => 5]);
        $this->actingAs($buyer)->post(route('orders.reviews.store', $order), ['product_id' => $product->id, 'rating' => 4])->assertStatus(422);
    }

    public function test_buyer_cannot_review_before_order_is_completed(): void
    {
        $buyer = User::factory()->create();
        $order = Order::create(['order_number' => 'ORD-REVIEW-002', 'buyer_id' => $buyer->id, 'subtotal' => 10000, 'total_amount' => 10000, 'status' => 'processing']);

        $this->actingAs($buyer)->post(route('orders.reviews.store', $order), ['product_id' => 1, 'rating' => 5])->assertStatus(422);
    }
}
