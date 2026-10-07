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
     * Menggunakan pola "single-shot SSE": stream mengirim data saat ini lalu
     * langsung tutup. Browser EventSource akan reconnect otomatis setiap ~3 detik
     * (retry default). Ini mencegah worker PHP diblokir lama oleh koneksi SSE
     * yang panjang, sehingga worker tetap tersedia untuk request lain.
     *
     * Kursor `?after=<id>` atau header `Last-Event-ID` dikirim browser pada
     * setiap reconnect, sehingga hanya notifikasi baru yang dikirim.
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();

        // Kursor kembali: query `?after=` menang atas header `Last-Event-ID`,
        // dan nilai negatif maupun kursor kosong dinormalkan menjadi 0.
        $cursor = (int) ($request->query('after') ?? $request->header('Last-Event-ID', 0));
        $after = max($cursor, 0);

        // Bebaskan session lock sebelum stream agar request lain tidak diblokir.
        if ($request->hasSession()) {
            $request->session()->save();
        }

        return response()->stream(function () use ($user, $after): void {
            // Beritahu browser untuk reconnect setiap 5 detik (bukan 3 detik default).
            echo "retry: 5000\n\n";
            $this->flushStream();

            // Koneksi pertama: patok kursor ke notifikasi terakhir yang sudah
            // ada agar riwayat lama tidak dikirim ulang sebagai toast.
            $lastId = $after === 0
                ? (int) UserNotification::query()->where('user_id', $user->id)->max('id')
                : $after;

            // Kirim badge count agar badge selalu akurat.
            $this->sendCountEvent($user);

            // Kirim notifikasi baru yang belum diterima client.
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
            }

            // Tutup stream; browser EventSource akan reconnect otomatis tiap 5 detik.
            // Pola single-shot ini memastikan worker PHP bebas segera (< 200ms per request).
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
