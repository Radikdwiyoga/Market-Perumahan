<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_login_records_audit_log(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->post('/login', ['login' => $user->phone, 'password' => 'password123'])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'USER_LOGIN']);
    }

    public function test_checkout_records_order_created_audit_log(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product('Beras', 50000);

        $this->cart($buyer, [$product->id => 1]);

        $this->actingAs($buyer)
            ->post(route('checkout.store'), [
                'shipping_methods' => [$product->seller_profile_id => 'seller_delivery'],
                'shipping_address' => 'Blok A2 No. 15',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['user_id' => $buyer->id, 'action' => 'ORDER_CREATED']);
    }

    public function test_seller_product_creation_records_audit_log(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Kopi', 'phone' => $seller->phone, 'address' => 'A1']);
        $category = Category::create(['name' => 'Minuman']);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), [
                'category_id' => $category->id,
                'name' => 'Kopi Susu',
                'price' => 15000,
                'stock' => 5,
            ])
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('audit_logs', ['user_id' => $seller->id, 'action' => 'PRODUCT_CREATED']);
        $this->assertTrue($store->products()->where('name', 'Kopi Susu')->exists());
    }

    public function test_admin_can_view_audit_logs_and_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk()->assertSee('Audit log');
        $this->actingAs($admin)->get(route('admin.audit-logs.index', ['q' => 'USER_LOGIN']))->assertOk();
    }

    public function test_buyer_cannot_access_audit_logs(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    private function product(string $name, int $price): Product
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $store = SellerProfile::create(['user_id' => $seller->id, 'store_name' => "Toko {$name}", 'phone' => $seller->phone, 'address' => 'Blok A1']);
        $category = Category::create(['name' => $name]);

        return Product::create([
            'seller_profile_id' => $store->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'stock' => 10,
        ]);
    }
}
