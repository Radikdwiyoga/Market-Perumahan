<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Optimasi gambar upload menjadi WebP (resize bila melebihi dimensi maksimal)
 * agar halaman katalog dan toko cepat dimuat (PRD §72 Performance).
 */
final class ImageOptimizer
{
    /**
     * Simpan gambar sebagai WebP di disk public.
     * Fail-soft: bila file tidak dapat didecode, simpan file asli apa adanya.
     */
    public static function store(UploadedFile $file, string $directory, int $maxDimension = 1600, int $quality = 82): string
    {
        $image = self::decode($file);

        if ($image === null) {
            return $file->store($directory, 'public');
        }

        try {
            $resized = self::resize($image, $maxDimension);

            $path = $directory.'/'.self::uniqueFileName($file).'.webp';
            self::save($resized, $path, $quality);

            return $path;
        } catch (RuntimeException) {
            return $file->store($directory, 'public');
        }
    }

    private static function decode(UploadedFile $file): ?GdImage
    {
        $path = $file->getPathname();
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => null,
        };

        return $image ?: null;
    }

    private static function resize(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $image;
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $resized = imagecreatetruecolor(max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Slug aman untuk nama file + suffix unik agar upload tidak saling timpa.
     */
    private static function uniqueFileName(UploadedFile $file): string
    {
        $name = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = preg_replace('/[^a-z0-9_-]+/', '-', $name) ?? 'image';
        $name = trim($name, '-') ?: 'image';

        return substr($name, 0, 60).'-'.substr(md5(uniqid('', true)), 0, 12);
    }

    private static function save(GdImage $image, string $path, int $quality): void
    {
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $written = imagewebp($image, null, $quality);
        $contents = $written ? ob_get_contents() : false;
        ob_end_clean();

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Gagal menyimpan gambar WebP.');
        }

        Storage::disk('public')->put($path, $contents);
        imagedestroy($image);
    }
}
