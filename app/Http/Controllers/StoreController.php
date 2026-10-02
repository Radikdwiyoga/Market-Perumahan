<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SellerProfile;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function show(SellerProfile $store): View
    {
        abort_unless($store->status === 'open', 404);

        $products = $store->products()
            ->where('status', 'active')
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->get();

        $categories = Category::query()
            ->where('status', 'active')
            ->whereHas('products', fn ($query) => $query->where('seller_profile_id', $store->id)->where('status', 'active'))
            ->orderBy('name')
            ->get();

        return view('stores.show', [
            'store' => $store,
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
