<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Controller
{
    /**
     * Pastikan toko sudah diverifikasi pengelola dan tidak sedang ditangguhkan
     * sebelum seller boleh mengubah data yang memengaruhi pembeli.
     *
     * Tanpa gerbang ini seller bisa membuka tokonya sendiri lewat form pengaturan
     * toko, sehingga verifikasi admin dilewati.
     */
    protected function ensureStoreCanOperate(SellerProfile $store): void
    {
        abort_if(! $store->isVerificationApproved(), 403, 'Toko Anda belum diverifikasi pengelola.');
        abort_if($store->status === 'suspended', 403, 'Toko Anda sedang ditangguhkan pengelola.');
    }

    /**
     * Buat respons unduhan CSV dengan BOM UTF-8 agar terbaca Excel.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function downloadCsv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, array_map(self::sanitizeCsvValue(...), $row));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Cegah formula injection pada ekspor CSV.
     *
     * Excel/LibreOffice mengeksekusi sel yang diawali `=`, `+`, `-`, atau `@`
     * sebagai formula, sehingga nama produk buatan seller bisa mengubah isi
     * laporan saat admin membukanya. Awalan sel berbahaya diganti marker `'` + teks.
     */
    private static function sanitizeCsvValue(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return $value === true ? '1' : '0';
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
