<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_their_store_profile(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Lama',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $response = $this->actingAs($seller)->put(route('seller.store.update'), [
            'store_name' => 'Warung Baru',
            'description' => 'Kebutuhan warga sekitar',
            'phone' => '081200001111',
            'address' => 'Blok B2',
            'status' => 'closed',
        ]);

        $response->assertRedirect(route('seller.store.edit'));
        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'store_name' => 'Warung Baru',
            'status' => 'closed',
        ]);
    }

    public function test_seller_can_set_business_hours(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Jam',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)->put(route('seller.store.update'), [
            'store_name' => 'Warung Jam',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'open_time' => '08:00',
            'close_time' => '21:00',
            'status' => 'open',
        ])->assertRedirect(route('seller.store.edit'));

        $this->assertSame('08:00 - 21:00', $store->refresh()->businessHoursLabel());
    }

    public function test_business_hours_must_be_a_valid_range(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Jam',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)->put(route('seller.store.update'), [
            'store_name' => 'Warung Jam',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'open_time' => '21:00',
            'close_time' => '08:00',
            'status' => 'open',
        ])->assertSessionHasErrors('close_time');
    }

    public function test_buyer_cannot_update_a_store_profile(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('seller.store.edit'))->assertForbidden();
    }

    public function test_store_image_is_optimized_to_webp(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Foto',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
        ]);

        $this->actingAs($seller)->put(route('seller.store.update'), [
            'store_name' => 'Warung Foto',
            'phone' => $seller->phone,
            'address' => 'Blok A1',
            'status' => 'open',
            'image' => UploadedFile::fake()->image('toko.png'),
        ])->assertRedirect(route('seller.store.edit'));

        $store = SellerProfile::firstOrFail();
        $this->assertStringEndsWith('.webp', $store->image);
        Storage::disk('public')->assertExists($store->image);
        $this->assertSame('image/webp', getimagesize(Storage::disk('public')->path($store->image))['mime']);
    }
}
