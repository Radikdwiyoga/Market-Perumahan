<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Batasi durasi stream agar pengujian selesai cepat (tanpa sleep/polling lama).
        config()->set('marketplace.realtime.max_wait', 0);
        config()->set('marketplace.realtime.poll_interval', 0);
    }

    public function test_first_connect_sends_count_without_replaying_history(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();

        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Pesanan diproses', 'body' => 'Toko memproses pesanan Anda.', 'type' => 'order']);
        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Pembayaran diverifikasi', 'body' => 'Bukti pembayaran Anda diterima.', 'type' => 'payment']);
        UserNotification::create(['user_id' => $other->id, 'title' => 'Pesanan rahasia', 'body' => 'Hanya milik pengguna lain.', 'type' => 'order']);

        $response = $this->actingAs($buyer)->get(route('notifications.stream'));

        $response->assertOk();
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('content-type', ''));

        $content = $response->streamedContent();

        // Koneksi pertama hanya mengirim hitungan unread, bukan riwayat lengkap.
        $this->assertStringContainsString('event: count', $content);
        $this->assertStringContainsString('"notifications":2', $content);
        $this->assertStringNotContainsString('event: notification', $content);
        $this->assertStringNotContainsString('Pesanan diproses', $content);
        $this->assertStringNotContainsString('Pesanan rahasia', $content);
    }

    public function test_stream_resumes_from_last_event_id(): void
    {
        $buyer = User::factory()->create();
        $first = UserNotification::create(['user_id' => $buyer->id, 'title' => 'Pertama', 'body' => 'Sudah pernah dikirim', 'type' => 'order']);
        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Kedua', 'body' => 'Notifikasi baru', 'type' => 'payment']);

        $response = $this->actingAs($buyer)->get(route('notifications.stream'), ['Last-Event-ID' => (string) $first->id]);

        $content = $response->streamedContent();

        $this->assertStringNotContainsString('Sudah pernah dikirim', $content);
        $this->assertStringContainsString('event: notification', $content);
        $this->assertStringContainsString('Notifikasi baru', $content);
    }

    public function test_stream_resumes_from_after_query_parameter(): void
    {
        $buyer = User::factory()->create();
        $first = UserNotification::create(['user_id' => $buyer->id, 'title' => 'Pertama', 'body' => 'Sudah pernah dikirim', 'type' => 'order']);
        UserNotification::create(['user_id' => $buyer->id, 'title' => 'Kedua', 'body' => 'Notifikasi baru', 'type' => 'payment']);

        // Client app.js selalu membawa kursor via ?after=<id> pada setiap EventSource baru.
        $response = $this->actingAs($buyer)->get(route('notifications.stream').'?after='.$first->id);

        $content = $response->streamedContent();

        $this->assertStringNotContainsString('Sudah pernah dikirim', $content);
        $this->assertStringContainsString('Notifikasi baru', $content);
    }

    public function test_stream_requires_authentication(): void
    {
        $this->get(route('notifications.stream'))->assertRedirect(route('login'));
    }

    /**
     * Heartbeat menjaga koneksi tetap hidup selama stream menunggu notifikasi
     * baru, dan payload-nya harus `{}` (bukan `[]`) sesuai format SSE.
     */
    public function test_idle_stream_sends_an_empty_object_heartbeat(): void
    {
        config()->set('marketplace.realtime.max_wait', 1);
        config()->set('marketplace.realtime.poll_interval', 1);

        $buyer = User::factory()->create();

        $content = $this->actingAs($buyer)->get(route('notifications.stream'))->streamedContent();

        $this->assertStringContainsString("event: heartbeat\ndata: {}\n\n", $content);
        $this->assertStringNotContainsString('data: []', $content);
    }

    /**
     * Client hanya menyambungkan EventSource bila halaman memuat penanda
     * [data-notifications-source]; komponen `<x-notification-stream />` adalah
     * satu-satunya sumber penanda tersebut.
     */
    public function test_dashboards_expose_the_stream_marker(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $seller = User::factory()->create(['role' => 'seller']);

        SellerProfile::create([
            'user_id' => $seller->id,
            'store_name' => 'Warung Warga',
            'phone' => $seller->phone,
            'address' => 'A1',
        ]);

        $dashboards = [
            [$buyer, route('dashboard')],
            [$admin, route('admin.dashboard')],
            [$seller, route('seller.dashboard')],
        ];

        foreach ($dashboards as [$user, $url]) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('data-notifications-source')
                ->assertSee(route('notifications.stream'))
                ->assertSee('data-user="'.$user->id.'"', escape: false);
        }
    }
}
