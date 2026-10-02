<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $carts) {}

    public function index(Request $request): View
    {
        $this->ensureBuyer();
        $buyer = auth()->user();
        $items = $this->carts->products($buyer);

        return view('cart.index', [
            'groups' => $items->groupBy(fn (Product $product) => $product->sellerProfile->id),
            'cartQuantities' => $this->carts->quantities($buyer),
            'cartNotes' => $this->carts->notes($buyer),
            'total' => $this->carts->subtotal($buyer),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->ensureBuyer();
        abort_unless($product->status === 'active' && $product->sellerProfile->status === 'open', 404);

        $quantity = $request->validate(['quantity' => ['nullable', 'integer', 'min:1']])['quantity'] ?? 1;
        $this->carts->add(auth()->user(), $product, $quantity);

        return back()->with('status', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->ensureBuyer();
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->carts->update(auth()->user(), $product, $validated['quantity'], $validated['note'] ?? null);

        return back()->with('status', 'Item keranjang diperbarui.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->ensureBuyer();
        $this->carts->remove(auth()->user(), $product);

        return back()->with('status', 'Produk dihapus dari keranjang.');
    }

    private function ensureBuyer(): void
    {
        abort_unless(auth()->user()->role === 'buyer', 403);
    }
}
