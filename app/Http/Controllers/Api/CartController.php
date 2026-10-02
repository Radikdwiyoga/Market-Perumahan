<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Support\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $carts) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureBuyer($request);
        $buyer = $request->user();

        $items = CartItem::query()
            ->with('product.sellerProfile')
            ->where('user_id', $buyer->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $items->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'product' => new ProductResource($item->product->load(['category', 'sellerProfile'])),
                'quantity' => $item->quantity,
                'note' => $item->note,
                'line_total' => $item->product->effectivePrice() * $item->quantity,
            ]),
            'meta' => [
                'count' => $this->carts->count($buyer),
                'subtotal' => $this->carts->subtotal($buyer),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureBuyer($request);
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::query()->with('sellerProfile')->findOrFail($validated['product_id']);
        abort_unless($product->status === 'active' && $product->sellerProfile->status === 'open', 404, 'Produk tidak ditemukan.');

        $this->carts->add($request->user(), $product, $validated['quantity']);

        return response()->json(['message' => 'Produk ditambahkan ke keranjang.'], 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($cartItem->user_id === $request->user()->id, 403, 'Item keranjang bukan milik Anda.');

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->carts->update($request->user(), $cartItem->product, $validated['quantity'], $validated['note'] ?? null);

        return response()->json(['message' => 'Item keranjang diperbarui.']);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($cartItem->user_id === $request->user()->id, 403, 'Item keranjang bukan milik Anda.');

        $cartItem->delete();

        return response()->json(['message' => 'Produk dihapus dari keranjang.']);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()->role === 'buyer', 403);
    }
}
