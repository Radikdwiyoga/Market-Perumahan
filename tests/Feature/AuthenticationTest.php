<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_resident_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Radik Dwiyoga',
            'phone' => '081234567891',
            'email' => 'radik@example.com',
            'address' => 'Perumahan ABC',
            'block' => 'A2',
            'house_number' => '15',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'phone' => '081234567891',
            'role' => 'buyer',
            'status' => 'active',
        ]);
    }

    public function test_an_active_user_can_login_with_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '081234567892',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'login' => $user->phone,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_seller_is_redirected_to_the_dashboard_after_login(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'phone' => '081234567893',
            'password' => 'password123',
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => $seller->phone,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($seller);
    }

    public function test_a_buyer_dashboard_contains_marketplace_navigation(): void
    {
        $buyer = User::factory()->create();

        $response = $this->actingAs($buyer)->get(route('dashboard'));

        $response->assertOk()->assertSee(route('marketplace.index'))->assertSee(route('cart.index'));
    }

    public function test_demo_buyer_can_login_from_seeded_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->post('/login', [
            'login' => 'demo.buyer@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame('buyer', auth()->user()->role);
    }
}
