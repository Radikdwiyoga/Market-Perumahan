<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\Review;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->status === 'active' && $product->sellerProfile->status === 'open', 404);

        $product->load(['category', 'sellerProfile.paymentSetting']);

        return view('products.show', [
            'product' => $product,
            'isFavorite' => auth()->check()
                ? Favorite::query()->where('user_id', auth()->id())->where('product_id', $product->id)->exists()
                : false,
            'reviews' => Review::query()
                ->with('buyer')
                ->where('product_id', $product->id)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
