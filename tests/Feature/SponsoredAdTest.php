<?php

namespace Tests\Feature;

use App\Models\SponsoredAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SponsoredAdTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_promotions_management(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($user)->get(route('admin.promotions.index'))->assertForbidden();
    }

    public function test_admin_can_view_promotions_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.promotions.index'))->assertOk();
    }

    public function test_admin_can_create_image_promotion(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->image('banner.jpg', 1200, 600);

        $response = $this->actingAs($admin)->post(route('admin.promotions.store'), [
            'title' => 'Sponsor Toko Berkah',
            'type' => 'image',
            'media' => $file,
            'link_url' => 'https://example.com',
            'caption' => 'Promo diskon belanja warga',
            'order' => 1,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.promotions.index'));
        $this->assertDatabaseHas('sponsored_ads', [
            'title' => 'Sponsor Toko Berkah',
            'type' => 'image',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_toggle_and_delete_promotion(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $ad = SponsoredAd::create([
            'title' => 'Promo Lama',
            'type' => 'image',
            'media_path' => 'promotions/dummy.jpg',
            'status' => 'active',
            'order' => 0,
        ]);

        $this->actingAs($admin)->patch(route('admin.promotions.toggle', $ad))->assertRedirect();
        $this->assertDatabaseHas('sponsored_ads', ['id' => $ad->id, 'status' => 'inactive']);

        $this->actingAs($admin)->delete(route('admin.promotions.destroy', $ad))->assertRedirect(route('admin.promotions.index'));
        $this->assertDatabaseMissing('sponsored_ads', ['id' => $ad->id]);
    }

    public function test_active_promotions_display_on_marketplace_catalog(): void
    {
        $activeAd = SponsoredAd::create([
            'title' => 'Sponsor Aktif',
            'type' => 'image',
            'media_path' => 'promotions/active.jpg',
            'status' => 'active',
            'order' => 0,
        ]);

        $inactiveAd = SponsoredAd::create([
            'title' => 'Sponsor Nonaktif',
            'type' => 'image',
            'media_path' => 'promotions/inactive.jpg',
            'status' => 'inactive',
            'order' => 1,
        ]);

        $response = $this->get(route('marketplace.index'));
        $response->assertOk();
        $response->assertSee('Sponsor Aktif');
        $response->assertDontSee('Sponsor Nonaktif');
    }
}
