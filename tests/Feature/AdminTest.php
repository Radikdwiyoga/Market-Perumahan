<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Panel admin')->assertSee('Pengguna')->assertSee('Kategori');
    }

    public function test_admin_dashboard_links_to_complaint_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.complaints.index'));
    }

    public function test_admin_can_create_and_deactivate_a_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Kesehatan',
            'description' => 'Produk kesehatan warga',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'Kesehatan')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.categories.toggle', $category))->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'status' => 'inactive']);
    }

    public function test_buyer_cannot_access_admin_panel(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($buyer)->get(route('admin.categories.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_buyer_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Buyer Baru', 'phone' => '081211111111', 'email' => 'buyer.baru@example.com',
            'address' => 'Perumahan ABC', 'block' => 'C1', 'house_number' => '12', 'role' => 'buyer',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'buyer.baru@example.com', 'role' => 'buyer']);
    }

    public function test_admin_can_create_a_seller_with_store_profile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Seller Baru', 'phone' => '081222222222', 'email' => 'seller.baru@example.com',
            'address' => 'Perumahan ABC', 'block' => 'C2', 'house_number' => '15', 'role' => 'seller',
            'password' => 'password123', 'password_confirmation' => 'password123', 'store_name' => 'Toko Baru',
            'store_phone' => '081222222222', 'store_address' => 'Blok C2', 'store_description' => 'Toko demo',
        ])->assertRedirect(route('admin.dashboard'));

        $seller = User::where('email', 'seller.baru@example.com')->firstOrFail();
        $this->assertDatabaseHas('seller_profiles', ['user_id' => $seller->id, 'store_name' => 'Toko Baru']);
        $this->assertInstanceOf(SellerProfile::class, $seller->sellerProfile);
    }

    public function test_admin_can_verify_seller_and_toggle_account_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller', 'status' => 'inactive']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Pending', 'phone' => $seller->phone, 'address' => 'C3', 'status' => 'suspended', 'verification_status' => 'pending', 'submitted_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.users.verify-seller', $seller))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $seller->id, 'status' => 'active']);
        $this->assertDatabaseHas('seller_profiles', ['id' => $store->id, 'status' => 'open', 'verification_status' => 'approved', 'verified_by' => $admin->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $seller->id, 'type' => 'seller_verified']);

        $this->actingAs($admin)->patch(route('admin.users.status', $seller))->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $seller->id, 'status' => 'inactive']);
        $this->assertDatabaseHas('seller_profiles', ['id' => $store->id, 'status' => 'suspended']);
    }

    public function test_admin_can_filter_user_list_by_seller_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Buyer Filter', 'role' => 'buyer']);
        $seller = User::factory()->create(['name' => 'Seller Filter', 'role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Filter', 'phone' => $seller->phone, 'address' => 'C4']);

        $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'seller']))->assertOk()->assertSee('Seller Filter')->assertDontSee('Buyer Filter');
    }
}
