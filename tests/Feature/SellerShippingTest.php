<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_shipping_settings(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)->put(route('seller.shipping.update'), [
            'enable_delivery' => '1',
            'enable_pickup' => '1',
            'delivery_fee' => 5000,
            'min_order_amount' => 25000,
            'free_shipping_threshold' => 100000,
        ])->assertRedirect(route('seller.shipping.edit'));

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'enable_delivery' => true,
            'enable_pickup' => true,
            'delivery_fee' => 5000,
            'min_order_amount' => 25000,
            'free_shipping_threshold' => 100000,
        ]);
    }

    public function test_seller_must_enable_at_least_one_shipping_method(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)
            ->put(route('seller.shipping.update'), [
                'delivery_fee' => 5000,
                'min_order_amount' => 0,
            ])
            ->assertSessionHasErrors('enable_delivery');
    }

    public function test_free_shipping_threshold_cannot_be_lower_than_minimum_order(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)
            ->put(route('seller.shipping.update'), [
                'enable_delivery' => '1',
                'enable_pickup' => '1',
                'delivery_fee' => 5000,
                'min_order_amount' => 50000,
                'free_shipping_threshold' => 25000,
            ])
            ->assertSessionHasErrors('free_shipping_threshold');
    }

    public function test_buyer_cannot_access_shipping_settings(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('seller.shipping.edit'))->assertForbidden();
    }
}
