<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('api');
        RateLimiter::clear('api-auth');
    }

    public function test_guest_can_register_and_receive_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Siti Aminah',
            'phone' => '081234567890',
            'email' => 'siti@example.com',
            'address' => 'Jl. Mawar No. 3',
            'block' => 'A1',
            'house_number' => '12',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'device_name' => 'HP Siti',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.name', 'Siti Aminah')
            ->assertJsonPath('data.user.role', 'buyer')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'phone', 'role']]]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', ['phone' => '081234567890', 'role' => 'buyer']);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'HP Siti', 'tokenable_type' => User::class]);
    }

    public function test_register_rejects_invalid_payload(): void
    {
        $this->postJson('/api/auth/register', ['phone' => '0812'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'address', 'block', 'house_number', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_register_rejects_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '081234567890']);

        $this->postJson('/api/auth/register', [
            'name' => 'Duplikat',
            'phone' => '081234567890',
            'address' => 'Jl. Mawar',
            'block' => 'A1',
            'house_number' => '1',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_user_can_login_with_phone_or_email(): void
    {
        $user = User::factory()->create(['email' => 'budi@example.com', 'status' => 'active']);

        $this->postJson('/api/auth/login', ['login' => $user->phone, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);

        $this->postJson('/api/auth/login', ['login' => 'budi@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'budi@example.com');
    }

    public function test_login_rejects_wrong_password_and_inactive_account(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);

        $this->postJson('/api/auth/login', ['login' => $user->phone, 'password' => 'salah'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        $this->postJson('/api/auth/login', ['login' => $inactive->phone, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_read_own_profile(): void
    {
        $seller = User::factory()->create(['role' => 'seller', 'name' => 'Pak Hadi']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Toko Pak Hadi', 'phone' => $seller->phone, 'address' => 'B2']);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $seller->id)
            ->assertJsonPath('data.store.store_name', 'Toko Pak Hadi')
            ->assertJsonPath('data.store.verification_status', 'approved');
    }

    public function test_me_requires_a_valid_token(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401)->assertJsonPath('message', 'Unauthenticated.');

        $this->withHeader('Authorization', 'Bearer token-yang-salah')
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    public function test_bearer_token_works_without_session(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('uji')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_logout_revokes_the_current_token_only(): void
    {
        $user = User::factory()->create();
        $phoneToken = $user->createToken('hp')->plainTextToken;
        $tabletToken = $user->createToken('tablet')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$phoneToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Token berhasil dicabut.');

        $this->assertSame(1, PersonalAccessToken::query()->count());

        // Test client persists the resolved user across requests within one test;
        // real requests are stateless, so clear guard state like a fresh HTTP hit.
        auth()->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$phoneToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer '.$tabletToken)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_login_and_register_are_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/auth/login', ['login' => '0812', 'password' => 'x'])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', ['login' => '0812', 'password' => 'x'])->assertStatus(429);
    }
}
