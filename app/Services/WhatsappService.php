<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kirim pesan WhatsApp via Fonnte API.
 *
 * Dokumentasi: https://fonnte.com/docs
 * Simpan token di .env: FONNTE_TOKEN=xxx
 */
class WhatsappService
{
    private const API_URL = 'https://api.fonnte.com/send';

    public function __construct(
        private readonly string $token = '',
        private readonly bool $enabled = false,
    ) {}

    /**
     * Kirim pesan teks ke satu nomor WhatsApp.
     *
     * @param  string  $phone  Nomor tujuan (format: 08xx atau 628xx)
     * @param  string  $message  Isi pesan
     */
    public function send(string $phone, string $message): bool
    {
        if (! $this->enabled || blank($this->token) || blank($phone)) {
            return false;
        }

        $phone = $this->normalizePhone($phone);

        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->post(self::API_URL, [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp send failed', [
                    'phone' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] === false) {
                Log::warning('WhatsApp API error', ['phone' => $phone, 'response' => $data]);

                return false;
            }

            return true;
        } catch (ConnectionException $e) {
            Log::error('WhatsApp connection error: '.$e->getMessage(), ['phone' => $phone]);

            return false;
        }
    }

    /**
     * Normalisasi nomor telepon ke format internasional (62xxx).
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        return $phone;
    }
}
