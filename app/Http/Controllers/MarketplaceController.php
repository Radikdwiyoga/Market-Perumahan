<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Support\CartService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function __construct(protected CartService $carts) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->value();
        $selectedCategory = $request->integer('category');

        $products = Product::query()
            ->with(['category', 'sellerProfile'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('products.status', 'active')
            ->whereHas('sellerProfile', fn ($query) => $query->where('status', 'open'))
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.description', 'like', "%{$search}%");
                });
            })
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory))
            ->latest()
            ->get();

        return view('marketplace.index', [
            'products' => $products,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'stores' => SellerProfile::query()
                ->where('status', 'open')
                ->whereHas('products', fn ($query) => $query->where('status', 'active'))
                ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
                ->orderByDesc('products_count')
                ->limit(6)
                ->get(),
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'cartCount' => auth()->check() && auth()->user()->role === 'buyer' ? $this->carts->count(auth()->user()) : 0,
        ]);
    }
}
