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
}
