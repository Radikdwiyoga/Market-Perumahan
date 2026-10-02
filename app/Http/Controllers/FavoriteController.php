<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        $this->ensureBuyer();

        $favorites = Favorite::query()
            ->with('product.sellerProfile')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(12);

        return view('favorites.index', ['favorites' => $favorites]);
    }

    public function store(Product $product): RedirectResponse
    {
        $this->ensureBuyer();
        abort_unless($product->status === 'active' && $product->sellerProfile->status === 'open', 404);

        Favorite::query()->firstOrCreate([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
        ]);

        return back()->with('status', 'Produk disimpan ke favorit.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureBuyer();

        Favorite::query()->where('user_id', auth()->id())->where('product_id', $product->id)->delete();

        return back()->with('status', 'Produk dihapus dari favorit.');
    }

    private function ensureBuyer(): void
    {
        abort_unless(auth()->user()->role === 'buyer', 403);
    }
}
