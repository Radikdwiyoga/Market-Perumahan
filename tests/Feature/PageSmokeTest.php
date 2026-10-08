<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Menembak setiap halaman GET bernama dengan tiga peran untuk memastikan
     * tidak ada tombol/menu yang berujung 500 (halaman error server).
     */
    public function test_every_named_get_page_avoids_server_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $seller->id, 'store_name' => 'Warung Warga', 'phone' => $seller->phone, 'address' => 'A1']);
        $buyer = User::factory()->create();

        $failures = [];

        foreach ($this->pages() as $uri) {
            foreach ([$admin, $seller, $buyer] as $user) {
                $status = $this->actingAs($user)->get($uri)->getStatusCode();

                if ($status >= 500) {
                    $failures[] = "{$uri} => {$status} (as {$user->role})";
                }
            }
        }

        $this->assertSame([], $failures, "Halaman dengan error server:\n".implode("\n", $failures));
    }

    /**
     * Daftar halaman GET tanpa parameter route. Halaman berparameter
     * (order, produk, chat, dsb.) sudah dicover test masing-masing.
     *
     * @return list<string>
     */
    private function pages(): array
    {
        $pages = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || $route->getName() === null) {
                continue;
            }

            if (str_contains($route->uri(), '{')) {
                continue;
            }

            // Stream SSE panjang; diuji terpisah di RealtimeNotificationTest.
            if (str_contains($route->uri(), 'stream')) {
                continue;
            }

            $pages[] = '/'.ltrim($route->uri(), '/');
        }

        sort($pages);

        return $pages;
    }
}
