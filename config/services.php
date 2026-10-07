<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google AI Studio (Gemini)
    |--------------------------------------------------------------------------
    |
    | Dipakai fitur "Buat deskripsi dengan AI" pada form produk penjual.
    | Ambil API key gratis di https://aistudio.google.com/apikey lalu isi
    | GOOGLE_AI_API_KEY pada .env. Kosongkan berarti fitur AI dimatikan.
    |
    */

    'google_ai' => [
        'key' => env('GOOGLE_AI_API_KEY'),
        'model' => env('GOOGLE_AI_MODEL', 'gemini-3.5-flash-lite'),

        // Model cadangan dipakai otomatis saat model utama sedang penuh (HTTP 503).
        'fallback_models' => ['gemini-3.5-flash', 'gemini-3.8-flash'],

        'endpoint' => env('GOOGLE_AI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp API Gateway
    |--------------------------------------------------------------------------
    |
    | Dipakai untuk mengirim notifikasi WhatsApp ke penjual saat ada pesanan baru.
    | Daftar & ambil token gratis di https://fonnte.com
    |
    | FONNTE_ENABLED=true   — aktifkan pengiriman WA
    | FONNTE_TOKEN=xxx      — token dari dashboard Fonnte
    |
    */

    'fonnte' => [
        'enabled' => env('FONNTE_ENABLED', false),
        'token' => env('FONNTE_TOKEN'),
    ],

];
