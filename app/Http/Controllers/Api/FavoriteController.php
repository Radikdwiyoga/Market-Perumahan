<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensureBuyer($request);
        $buyer = $request->user();

        $favorites = Favorite::query()
            ->with(['product.category', 'product.sellerProfile', 'product.reviews'])
            ->where('user_id', $buyer->id)
            ->latest()
            ->paginate($request->integer('per_page', 15) ?: 15)
            ->withQueryString();

        return ProductResource::collection($favorites->through(
            fn (Favorite $favorite): Product => $favorite->product
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureBuyer($request);
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $product = Product::query()->with('sellerProfile')->findOrFail($validated['product_id']);
        abort_unless($product->status === 'active' && $product->sellerProfile->status === 'open', 404, 'Produk tidak ditemukan.');

        Favorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return response()->json(['message' => 'Produk disimpan ke favorit.'], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureBuyer($request);

        Favorite::query()->where('user_id', $request->user()->id)->where('product_id', $product->id)->delete();

        return response()->json(['message' => 'Produk dihapus dari favorit.']);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()->role === 'buyer', 403);
    }
}
