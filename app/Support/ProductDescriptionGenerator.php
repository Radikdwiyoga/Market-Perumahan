<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Membuat deskripsi produk berbahasa Indonesia dari foto produk memakai
 * Google AI Studio (Gemini vision). Credential dibaca dari config/services.php
 * (`services.google_ai`) yang diisi dari env GOOGLE_AI_API_KEY.
 */
final class ProductDescriptionGenerator
{
    /**
     * Batas aman di bawah batas 2000 karakter milik kolom `products.description`.
     */
    private const MAX_LENGTH = 1900;

    /**
     * Batas base64 inline sebelum foto diperkecil (Gemini menerima ~20 MB payload).
     */
    private const MAX_INLINE_BYTES = 4 * 1024 * 1024;

    private const PROMPT = <<<'PROMPT'
        Kamu adalah copywriter marketplace barang untuk warga sebuah perumahan. Tugasmu menulis deskripsi produk berbahasa Indonesia berdasarkan foto yang dikirim.

        Aturan:
        1. Tulis 3-5 kalimat singkat yang menjelaskan jenis barang, kondisi, warna atau bahan, dan kegunaannya.
        2. Sebut hanya apa yang benar-benar terlihat di foto. Jangan mengarang merek, ukuran, berat, garansi, stok, atau harga.
        3. Gunakan nada ramah dan mudah dibaca.
        4. Tanpa markdown, tanpa judul, tanpa bullet, tanpa emoji, tanpa tanda kutip pembuka.
        5. Balas hanya teks deskripsi.
        PROMPT;

    /**
     * Fitur aktif hanya bila API key tersedia di environment.
     */
    public static function isConfigured(): bool
    {
        return filled(config('services.google_ai.key'));
    }

    /**
     * Hasilkan deskripsi dari foto produk.
     *
     * @param  array{name?: string|null, category?: string|null}  $context
     *
     * @throws RuntimeException bila key kosong, foto tidak terbaca, atau panggilan API gagal.
     */
    public static function generate(UploadedFile $image, array $context = []): string
    {
        if (! self::isConfigured()) {
            throw new RuntimeException('Fitur AI belum dikonfigurasi. Isi GOOGLE_AI_API_KEY pada file .env.');
        }

        $payload = self::payload($image, $context);
        $failure = null;

        foreach (self::modelChain() as $model) {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::endpoint($model), $payload);

            if ($response->successful()) {
                $text = self::extractText($response->json());

                if ($text !== '') {
                    return $text;
                }

                $failure = ['status' => 200, 'body' => 'empty response'];

                continue;
            }

            Log::warning('Google AI gagal membuat deskripsi produk.', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // 400/401/403/404 = masalah konfigurasi yang tidak akan membaik dengan
            // mencoba model lain, jadi langsung hentikan rantai.
            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                throw new RuntimeException(self::errorMessage($response->status()));
            }

            $failure = ['status' => $response->status(), 'body' => $response->body()];
        }

        throw new RuntimeException(self::errorMessage($failure['status'] ?? 503));
    }

    /**
     * Payload generateContent: foto sebagai inline_data + prompt teks.
     *
     * @param  array{name?: string|null, category?: string|null}  $context
     * @return array<string, mixed>
     */
    private static function payload(UploadedFile $image, array $context): array
    {
        return [
            'contents' => [
                [
                    'parts' => [
                        ['inline_data' => self::inlineImage($image)],
                        ['text' => self::promptFor($context)],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500,
                'responseMimeType' => 'text/plain',
            ],
        ];
    }

    /**
     * Model utama lalu model cadangan. Google sering membalas 503 saat sebuah model
     * sedang penuh, jadi pindah ke model berikutnya lebih baik daripada langsung gagal.
     *
     * @return array<int, string>
     */
    private static function modelChain(): array
    {
        $models = array_merge(
            [(string) config('services.google_ai.model', 'gemini-3.8-flash')],
            (array) config('services.google_ai.fallback_models', []),
        );

        return array_values(array_unique(array_filter($models)));
    }

    /**
     * Susun prompt; nama dan kategori dari form dipakai sebagai petunjuk opsional.
     *
     * @param  array{name?: string|null, category?: string|null}  $context
     */
    private static function promptFor(array $context): string
    {
        $hints = [];

        if (filled($context['name'] ?? null)) {
            $hints[] = 'Nama produk yang dipakai penjual: '.$context['name'].'.';
        }

        if (filled($context['category'] ?? null)) {
            $hints[] = 'Kategori produk: '.$context['category'].'.';
        }

        if ($hints === []) {
            return self::PROMPT;
        }

        return self::PROMPT."\n\nPetunjuk dari penjual:\n- ".implode("\n- ", $hints);
    }

    /**
     * Inline image untuk payload Gemini: mime type asli + base64.
     *
     * @return array{mime_type: string, data: string}
     */
    private static function inlineImage(UploadedFile $image): array
    {
        $contents = (string) file_get_contents($image->getRealPath());

        if (strlen($contents) <= self::MAX_INLINE_BYTES) {
            return ['mime_type' => self::mimeType($image), 'data' => base64_encode($contents)];
        }

        $shrunk = self::shrink($contents);

        if ($shrunk === null) {
            throw new RuntimeException('Foto terlalu besar atau tidak dapat diproses.');
        }

        return ['mime_type' => 'image/jpeg', 'data' => base64_encode($shrunk)];
    }

    /**
     * Perkecil foto besar ( sisi terpanjang 1280px ) memakai GD, hasil JPEG.
     */
    private static function shrink(string $contents): ?string
    {
        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return null;
        }

        $max = 1280;
        $scale = min($max / imagesx($source), $max / imagesy($source), 1);
        $width = max(1, (int) round(imagesx($source) * $scale));
        $height = max(1, (int) round(imagesy($source) * $scale));

        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        imagedestroy($source);

        ob_start();
        $ok = imagejpeg($target, null, 80);
        $encoded = $ok ? (string) ob_get_contents() : '';
        ob_end_clean();
        imagedestroy($target);

        return $encoded === '' ? null : $encoded;
    }

    /**
     * Ambil teks dari struktur respons Gemini, lalu rapikan (buang markdown/quote).
     *
     * @param  array<string, mixed>|null  $json
     */
    private static function extractText(?array $json): string
    {
        $parts = data_get($json, 'candidates.0.content.parts');

        if (! is_array($parts)) {
            return '';
        }

        $text = collect($parts)
            ->pluck('text')
            ->filter(fn ($part) => is_string($part))
            ->implode(' ');

        $text = trim($text);
        $text = (string) preg_replace('/^\s*[#*>+-]+\s*/mu', '', $text);
        $text = (string) preg_replace('/\*{1,3}/u', '', $text);
        $text = trim((string) preg_replace('/[ \t]+/u', ' ', $text));
        $text = trim($text, "\"“”'‘’`");

        return Str::limit($text, self::MAX_LENGTH);
    }

    /**
     * Terjemahkan status error upstream menjadi pesan ramah untuk penjual.
     */
    private static function errorMessage(int $status): string
    {
        return match ($status) {
            400, 401, 403 => 'API key Google AI ditolak. Periksa kembali GOOGLE_AI_API_KEY.',
            404 => 'Model AI yang dikonfigurasi tidak tersedia. Periksa GOOGLE_AI_MODEL pada .env.',
            429 => 'Batas pemakaian API AI tercapai. Coba lagi beberapa menit lagi.',
            500, 502, 503, 504 => 'Layanan AI sedang sibuk atau tidak tersedia. Silakan tulis deskripsi secara manual.',
            default => 'Layanan AI sedang tidak tersedia. Silakan tulis deskripsi secara manual.',
        };
    }

    private static function endpoint(string $model): string
    {
        $base = rtrim((string) config('services.google_ai.endpoint'), '/');

        return $base.'/'.rawurlencode($model).':generateContent?key='.urlencode((string) config('services.google_ai.key'));
    }

    private static function mimeType(UploadedFile $image): string
    {
        $detected = $image->getMimeType();

        return in_array($detected, ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'], true)
            ? $detected
            : 'image/jpeg';
    }
}
