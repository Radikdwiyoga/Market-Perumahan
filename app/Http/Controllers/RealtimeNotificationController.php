<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RealtimeNotificationController extends Controller
{
    /**
     * Stream notifikasi pengguna via Server-Sent Events (PRD §70).
     *
     * Koneksi pertama (tanpa kursor) TIDAK mengulang riwayat: cukup mengirim
     * event `count` (jumlah unread) lalu menunggu notifikasi baru. Koneksi
     * berikutnya membawa kursor `?after=<id>` (atau header Last-Event-ID)
     * sehingga hanya notifikasi dengan id lebih besar yang dikirim. Teknik ini
     * mencegah toast lama muncul berulang setiap reconnect.
     *
     * Stream menutup diri setelah `max_wait` detik idle (heartbeat tiap
     * `poll_interval` detik); browser akan reconnect membawa kursor terakhir.
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();

        // Kursor kembali: query `?after=` menang atas header `Last-Event-ID`,
        // dan nilai negatif maupun kursor kosong dinormalkan menjadi 0.
        $cursor = (int) ($request->query('after') ?? $request->header('Last-Event-ID', 0));
        $after = max($cursor, 0);

        $maxWait = (int) config('marketplace.realtime.max_wait', 25);
        $interval = (int) config('marketplace.realtime.poll_interval', 3);

        return response()->stream(function () use ($user, $after, $maxWait, $interval): void {
            $started = now()->getTimestamp();

            // Koneksi pertama: patok kursor ke notifikasi terakhir yang sudah
            // ada agar riwayat lama tidak dikirim ulang sebagai toast.
            $lastId = $after === 0
                ? (int) UserNotification::query()->where('user_id', $user->id)->max('id')
                : $after;

            $this->sendCountEvent($user);

            while (true) {
                $notifications = UserNotification::query()
                    ->where('user_id', $user->id)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->limit(50)
                    ->get();

                foreach ($notifications as $notification) {
                    // Baris `id:` mendahului frame notifikasi agar EventSource
                    // dapat melanjutkan dari event terakhir saat koneksi terputus.
                    echo "id: {$notification->id}\n";
                    $this->writeEvent('notification', [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'type' => $notification->type,
                        'created_at' => $notification->created_at?->toIso8601String(),
                    ]);

                    $lastId = $notification->id;
                }

                $elapsed = now()->getTimestamp() - $started;

                if ($elapsed >= $maxWait) {
                    break;
                }

                $this->writeEvent('heartbeat', new stdClass);

                sleep($interval);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, private',
            'X-Accel-Buffering' => 'no',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Kirim jumlah notifikasi & chat belum dibaca agar badge bisa disinkronkan
     * secara akurat (bukan sekadar ditambah satu per notifikasi baru).
     */
    private function sendCountEvent(User $user): void
    {
        $unread = UserNotification::query()
            ->where('user_id', $user->id)
            ->unread()
            ->count();

        $this->writeEvent('count', [
            'notifications' => $unread,
            'chats' => ChatConversation::unreadConversationCountFor($user),
        ]);
    }

    /**
     * Tulis satu frame SSE (`event:` + `data:`) lalu flush agar terkirim sekarang.
     *
     * Payload berupa `stdClass` (bukan array kosong) agar frame tanpa data tetap
     * ter-encode sebagai `{}` sesuai format SSE, bukan `[]`.
     *
     * @param  array<string, mixed>|object  $payload
     */
    private function writeEvent(string $event, array|object $payload): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
        $this->flushStream();
    }

    private function flushStream(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
