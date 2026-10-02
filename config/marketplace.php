<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Realtime (PRD §70)
    |--------------------------------------------------------------------------
    |
    | Pengaturan transport notifikasi real-time via Server-Sent Events.
    | Pengujian feature dapat mengeset nilai ini agar stream segera ditutup.
    |
    */

    'realtime' => [
        // Detik maksimum stream tetap terbuka saat tidak ada notifikasi baru.
        'max_wait' => (int) env('REALTIME_MAX_WAIT', 25),

        // Detik antara dua pengecekan notifikasi baru saat stream idle.
        'poll_interval' => (int) env('REALTIME_POLL_INTERVAL', 3),

        // Batas request SSE per menit (EventSource reconnect secara berkala).
        'limit_per_minute' => (int) env('REALTIME_LIMIT_PER_MINUTE', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pembayaran (PRD §14, §61)
    |--------------------------------------------------------------------------
    |
    | Batas waktu pembayaran sebelum sub-order otomatis dibatalkan oleh
    | `orders:cancel-expired`. Berlaku untuk COD dan pembayaran gateway.
    |
    */

    'payment_expiry_minutes' => (int) env('PAYMENT_EXPIRY_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Backup harian (PRD §73–74)
    |--------------------------------------------------------------------------
    |
    | Backup database dan file upload dijalankan harian via jadwal `site:backup`.
    | Retention dapat dikonfigurasi (contoh PRD: 7 / 30 / 90 hari).
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Fitur AI (deskripsi produk otomatis)
    |--------------------------------------------------------------------------
    |
    | Credential Google AI Studio dibaca dari config/services.php (GOOGLE_AI_API_KEY).
    | Pengaturan di bawah hanya mengatur laju request ke API tersebut.
    |
    */

    'ai' => [
        // Batas request "Buat deskripsi dengan AI" per menit per seller.
        // Panggilan ini memakai kuota Google AI Studio, jadi default-nya konservatif.
        'limit_per_minute' => (int) env('AI_DESCRIPTION_LIMIT_PER_MINUTE', 6),
    ],

    'backup' => [
        // Berapa lama snapshot backup dipertahankan sebelum dibersihkan otomatis.
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 90),

        // Root folder penyimpanan snapshot backup (satu folder per eksekusi).
        'directory' => storage_path('backups'),
    ],
];
