<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_submit_a_seller_application(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->get(route('seller.application.create'))
            ->assertOk()
            ->assertSee('Ajukan toko Anda');

        $this->actingAs($buyer)
            ->post(route('seller.application.store'), [
                'store_name' => 'Warung Sembako Bu Tuti',
                'description' => 'Menjual beras, gula, dan minyak goreng.',
                'phone' => '081200000001',
                'address' => 'Blok C2 No. 15',
                'open_time' => '07:00',
                'close_time' => '20:00',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['id' => $buyer->id, 'role' => 'seller']);
        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $buyer->id,
            'store_name' => 'Warung Sembako Bu Tuti',
            'status' => 'closed',
            'verification_status' => 'pending',
        ]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $admin->id, 'type' => 'seller_verification_pending']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SELLER_APPLICATION_SUBMITTED']);
    }

    public function test_pending_store_is_hidden_from_the_public_storefront(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($buyer, 'Toko Menunggu Verifikasi');

        $store = SellerProfile::query()->where('user_id', $buyer->id)->firstOrFail();

        $this->get(route('stores.show', $store))->assertNotFound();
        $this->get(route('marketplace.index'))->assertOk()->assertDontSee('Toko Menunggu Verifikasi');
    }

    public function test_seller_cannot_submit_twice_while_pending(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($buyer, 'Toko Tunggal');
        $buyer->refresh();

        $this->actingAs($buyer)->get(route('seller.application.create'))->assertStatus(422);
        $this->actingAs($buyer)->post(route('seller.application.store'), ['store_name' => 'Toko Ganti'])->assertStatus(422);

        $this->assertDatabaseHas('seller_profiles', ['user_id' => $buyer->id, 'store_name' => 'Toko Tunggal']);
    }

    public function test_admin_can_approve_a_pending_store_and_open_the_storefront(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko Disetujui');
        $seller->refresh();
        $store = SellerProfile::query()->where('user_id', $seller->id)->firstOrFail();

        $this->actingAs($admin)->patch(route('admin.users.verify-seller', $seller))->assertRedirect();

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $store->id,
            'status' => 'open',
            'verification_status' => 'approved',
            'rejection_reason' => null,
            'verified_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $seller->id, 'type' => 'seller_verified']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SELLER_VERIFIED']);

        $this->get(route('stores.show', $store->refresh()))->assertOk()->assertSee('Toko Disetujui');
    }

    public function test_admin_can_reject_a_pending_store_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko Ditolak');

        $this->actingAs($admin)
            ->patch(route('admin.users.reject-seller', $seller), ['rejection_reason' => 'Data toko belum lengkap.'])
            ->assertRedirect();

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'status' => 'closed',
            'verification_status' => 'rejected',
            'rejection_reason' => 'Data toko belum lengkap.',
        ]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $seller->id, 'type' => 'seller_rejected']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SELLER_REJECTED']);
    }

    public function test_rejecting_a_store_requires_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko Tanpa Alasan');

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.reject-seller', $seller))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('seller_profiles', ['user_id' => $seller->id, 'verification_status' => 'pending']);
    }

    public function test_rejected_seller_can_resubmit_with_corrected_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko Kurang Lengkap');

        $this->actingAs($admin)->patch(route('admin.users.reject-seller', $seller), ['rejection_reason' => 'Alamat tidak jelas.']);

        $this->actingAs($seller)
            ->get(route('seller.application.create'))
            ->assertOk()
            ->assertSee('Pengajuan sebelumnya ditolak')
            ->assertSee('Alamat tidak jelas.');

        $this->actingAs($seller)
            ->post(route('seller.application.store'), [
                'store_name' => 'Toko Sudah Lengkap',
                'phone' => '081200000009',
                'address' => 'Blok D4 No. 8',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'store_name' => 'Toko Sudah Lengkap',
            'status' => 'closed',
            'verification_status' => 'pending',
            'rejection_reason' => null,
        ]);
    }

    public function test_admin_cannot_verify_a_store_that_is_not_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Aktif', 'phone' => $seller->phone, 'address' => 'A1']);

        $this->actingAs($admin)->patch(route('admin.users.verify-seller', $seller))->assertStatus(422);
        $this->actingAs($admin)->patch(route('admin.users.reject-seller', $seller), ['rejection_reason' => 'Tidak berlaku.'])->assertStatus(422);
    }

    public function test_non_admin_cannot_review_seller_applications(): void
    {
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko neta');
        $seller->refresh();

        $this->actingAs($seller)->patch(route('admin.users.verify-seller', $seller))->assertForbidden();
        $this->actingAs($seller)->patch(route('admin.users.reject-seller', $seller), ['rejection_reason' => 'x'])->assertForbidden();
    }

    public function test_admin_dashboard_and_user_list_surface_pending_applications(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'buyer']);
        $this->submitApplication($seller, 'Toko Menunggu Tinjau');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('1 pengajuan toko menunggu tinjauan Anda.');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['verification' => 'pending']))
            ->assertOk()
            ->assertSee('Toko Menunggu Tinjau')
            ->assertSee('Menunggu verifikasi')
            ->assertSee('Setujui toko');
    }

    public function test_dashboard_shows_the_right_verification_state_for_each_stage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $approvedSeller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $approvedSeller->id, 'store_name' => 'Toko Terverifikasi', 'phone' => $approvedSeller->phone, 'address' => 'A1']);

        $this->actingAs($buyer)->get(route('dashboard'))->assertOk()->assertSee('Daftar jadi pedagang');

        $this->submitApplication($buyer, 'Toko Menunggu');
        $this->actingAs($buyer)->get(route('dashboard'))->assertOk()->assertSee('sedang ditinjau pengelola');

        $this->actingAs($admin)->patch(route('admin.users.reject-seller', $buyer->refresh()), ['rejection_reason' => 'Data toko belum lengkap.'])->assertRedirect();
        $this->actingAs($buyer)->get(route('dashboard'))->assertOk()->assertSee('Perbaiki data toko Anda lalu kirim ulang');

        $this->actingAs($approvedSeller)->get(route('dashboard'))->assertOk()->assertSee('Buka dashboard toko');
    }

    private function submitApplication(User $user, string $storeName): void
    {
        $this->actingAs($user)
            ->post(route('seller.application.store'), [
                'store_name' => $storeName,
                'description' => 'Deskripsi toko.',
                'phone' => '081200000002',
                'address' => 'Blok B1 No. 2',
                'open_time' => '08:00',
                'close_time' => '19:00',
            ])
            ->assertRedirect(route('dashboard'));
    }
}
