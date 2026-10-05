<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\SellerProfile;
use App\Support\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SellerProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $store = $this->sellerStore($request);

        $products = $store->products()
            ->with('category')
            ->latest('id')
            ->paginate($request->integer('per_page', 15) ?: 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Request $request, int $product): ProductResource
    {
        $store = $this->sellerStore($request);

        return new ProductResource($store->products()->with('category')->findOrFail($product));
    }

    public function store(Request $request): JsonResponse
    {
        $store = $this->sellerStore($request);
        $this->ensureStoreCanOperate($store);
        $validated = $this->validated($request);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageOptimizer::store($request->file('image'), 'products');
        }

        $product = $store->products()->create($validated);
        AuditLogger::log('PRODUCT_CREATED', 'Product', $product->id, [
            'name' => $product->name,
            'price' => $product->price,
            'discount_percent' => $product->discount_percent,
            'stock' => $product->stock,
        ]);

        return (new ProductResource($product->load('category')))->response()->setStatusCode(201);
    }

    public function update(Request $request, int $product): ProductResource
    {
        $store = $this->sellerStore($request);
        $this->ensureStoreCanOperate($store);
        $product = $store->products()->findOrFail($product);
        $validated = $this->validated($request);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageOptimizer::store($request->file('image'), 'products');

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        } elseif ($request->boolean('remove_image') && $product->image) {
            Storage::disk('public')->delete($product->image);
            $validated['image'] = null;
        }

        $product->update($validated);
        AuditLogger::log('PRODUCT_UPDATED', 'Product', $product->id, [
            'name' => $product->name,
            'price' => $product->price,
            'discount_percent' => $product->discount_percent,
            'stock' => $product->stock,
        ]);

        return new ProductResource($product->load('category'));
    }

    public function destroy(Request $request, int $product): JsonResponse
    {
        $store = $this->sellerStore($request);
        $product = $store->products()->findOrFail($product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        AuditLogger::log('PRODUCT_DELETED', 'Product', $product->id, ['name' => $product->name]);

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'between:0,100'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    private function sellerStore(Request $request): SellerProfile
    {
        abort_unless($request->user()->isSeller(), 403);
        $store = $request->user()->sellerProfile()->first();

        return $store ?? abort(403, 'Toko tidak ditemukan.');
    }
}
