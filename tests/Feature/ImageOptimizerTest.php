<?php

namespace Tests\Feature;

use App\Support\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_converts_a_large_png_to_a_resized_webp(): void
    {
        Storage::fake('public');
        $temp = tempnam(sys_get_temp_dir(), 'foto').'.png';
        $png = imagecreatetruecolor(2000, 1200);
        imagefill($png, 0, 0, imagecolorallocate($png, 16, 185, 129));
        imagepng($png, $temp);
        imagedestroy($png);

        $path = ImageOptimizer::store(new UploadedFile($temp, 'foto-produk.png', 'image/png', null, true), 'products');

        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);
        $info = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(1600, $info[0]);
        $this->assertSame(960, $info[1]);
    }

    public function test_store_falls_back_to_the_original_file_when_it_cannot_be_decoded(): void
    {
        Storage::fake('public');
        $temp = tempnam(sys_get_temp_dir(), 'plain').'.png';
        file_put_contents($temp, 'bukan-file-gambar');

        $path = ImageOptimizer::store(new UploadedFile($temp, 'dokumen.png', 'image/png', null, true), 'products');

        Storage::disk('public')->assertExists($path);
        $this->assertStringNotContainsString('.webp', $path);
    }

    public function test_store_skips_images_beyond_the_pixel_budget(): void
    {
        Storage::fake('public');
        $temp = tempnam(sys_get_temp_dir(), 'besar').'.png';

        // Header PNG sah yang mendeklarasikan 9000x9000 (81 MP): cukup untuk
        // membuat GD kehabisan memori. Isi gambarnya sengaja tidak ada, jadi
        // file ini hanya bisa lolos bila batas piksel diperiksa sebelum decode.
        $ihdr = pack('NN', 9000, 9000)."\x08\x02\x00\x00\x00";
        $chunk = 'IHDR'.$ihdr;
        file_put_contents($temp, "\x89PNG\r\n\x1a\n".pack('N', 13).$chunk.pack('N', crc32($chunk)));

        $info = getimagesize($temp);
        $this->assertIsArray($info, 'Header gambar harus terbaca agar batas piksel yang diuji.');
        $this->assertSame(81_000_000, $info[0] * $info[1]);

        $path = ImageOptimizer::store(new UploadedFile($temp, 'besar.png', 'image/png', null, true), 'products');

        Storage::disk('public')->assertExists($path);
        $this->assertStringNotContainsString('.webp', $path, 'Gambar melebihi batas piksel disimpan apa adanya, tanpa didekode GD.');
    }
}
