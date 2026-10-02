<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'store' => ['nullable', 'integer', 'exists:seller_profiles,id'],
            'min_price' => ['nullable', 'integer', 'min:0'],
            'max_price' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:latest,price_asc,price_desc,name'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = $validated['q'] ?? null;

        $products = Product::query()
            ->with(['category', 'sellerProfile'])
            ->withAvg('reviews as reviews_avg', 'rating')
            ->withCount('reviews')
            ->where('products.status', 'active')
            ->whereHas('sellerProfile', fn ($query) => $query->where('status', 'open'))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.description', 'like', "%{$search}%")))
            ->when($validated['category'] ?? null, fn ($query) => $query->where('category_id', $validated['category']))
            ->when($validated['store'] ?? null, fn ($query) => $query->where('seller_profile_id', $validated['store']))
            ->when($validated['min_price'] ?? null, fn ($query) => $query->where('price', '>=', $validated['min_price']))
            ->when($validated['max_price'] ?? null, fn ($query) => $query->where('price', '<=', $validated['max_price']))
            ->when($validated['in_stock'] ?? false, fn ($query) => $query->where('stock', '>', 0))
            ->when(($validated['sort'] ?? 'latest') === 'price_asc', fn ($query) => $query->orderBy('price'))
            ->when(($validated['sort'] ?? 'latest') === 'price_desc', fn ($query) => $query->orderByDesc('price'))
            ->when(($validated['sort'] ?? 'latest') === 'name', fn ($query) => $query->orderBy('name'))
            ->latest('id')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless(
            $product->status === 'active' && $product->sellerProfile->status === 'open',
            404,
            'Produk tidak ditemukan.',
        );

        $product->load(['category', 'sellerProfile'])
            ->loadAvg('reviews as reviews_avg', 'rating')
            ->loadCount('reviews');

        return new ProductResource($product);
    }

    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->withCount(['products' => fn ($query) => $query->where('status', 'active')])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories)
            ->additional(['meta' => ['total' => $categories->count()]]);
    }

    public function stores(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('q')->trim()->value();

        $stores = SellerProfile::query()
            ->where('status', 'open')
            ->whereHas('products', fn ($query) => $query->where('status', 'active'))
            ->when($search, fn ($query) => $query->where('store_name', 'like', "%{$search}%"))
            ->withAvg('reviews as reviews_avg', 'rating')
            ->withCount(['reviews', 'products' => fn ($query) => $query->where('status', 'active')])
            ->orderByDesc('products_count')
            ->latest('id')
            ->paginate($request->integer('per_page', 15) ?: 15)
            ->withQueryString();

        return StoreResource::collection($stores);
    }

    public function store(SellerProfile $store): StoreResource
    {
        abort_unless($store->status === 'open', 404, 'Toko tidak ditemukan.');

        $store->loadAvg('reviews as reviews_avg', 'rating')->loadCount(['reviews', 'products' => fn ($query) => $query->where('status', 'active')]);

        return new StoreResource($store);
    }
}
