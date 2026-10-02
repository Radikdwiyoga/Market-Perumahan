<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\ProductDescriptionGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/**
 * Endpoint AJAX "Buat deskripsi dengan AI" untuk form tambah/edit produk penjual.
 *
 * Menerima file foto (opsional bila produk sudah punya foto tersimpan), nama, dan
 * kategori untuk membantu AI menulis deskripsi yang relevan.
 */
class SellerProductDescriptionAiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isSeller(), 403);
        $request->user()->sellerProfile()->firstOrFail();

        $validated = $request->validate([
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'existing_image' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ]);

        if (! ProductDescriptionGenerator::isConfigured()) {
            return response()->json([
                'message' => 'Fitur AI belum aktif. Isi GOOGLE_AI_API_KEY pada file .env lalu jalankan php artisan config:clear.',
            ], 503);
        }

        $image = $this->resolveImage($request, $validated);

        if ($image === null) {
            return response()->json([
                'message' => 'Pilih foto produk terlebih dahulu, atau simpan foto pada produk yang sudah ada.',
                'errors' => ['image' => ['Foto produk diperlukan untuk membuat deskripsi dengan AI.']],
            ], 422);
        }

        $category = isset($validated['category_id'])
            ? Category::query()->find($validated['category_id'])
            : null;

        try {
            $description = ProductDescriptionGenerator::generate($image, [
                'name' => $validated['name'] ?? null,
                'category' => $category?->name,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Gagal membuat deskripsi dengan AI. Silakan coba lagi.',
            ], 422);
        }

        return response()->json(['description' => $description]);
    }

    /**
     * Sumber foto: file baru diunggah, atau foto produk yang sudah tersimpan.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveImage(Request $request, array $validated): ?UploadedFile
    {
        if ($request->hasFile('image')) {
            return $request->file('image');
        }

        $path = $validated['existing_image'] ?? null;

        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return new UploadedFile(
            Storage::disk('public')->path($path),
            basename($path),
            null,
            null,
            true,
        );
    }
}
