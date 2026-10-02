<?php

namespace App\Support;

final class WhatsApp
{
    /**
     * Ubah nomor ke format internasional untuk wa.me:
     * "081234567890" => "6281234567890", "+62 812-3456" => "628123456".
     */
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62') && str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    public static function chatLink(string $phone, string $text = ''): string
    {
        $url = 'https://wa.me/'.self::normalize($phone);

        if ($text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }
}
