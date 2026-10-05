<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SellerProfile;
use App\Support\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SellerProductController extends Controller
{
    public function index(): View
    {
        $store = $this->sellerStore();

        return view('seller.products.index', [
            'store' => $store,
            'products' => $store->products()->with('category')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        $this->sellerStore();

        return view('seller.products.create', [
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $store = $this->sellerStore();
        $this->ensureStoreCanOperate($store);
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'between:0,100'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageOptimizer::store($request->file('image'), 'products');
        }

        $product = $store->products()->create($validated);
        AuditLogger::log('PRODUCT_CREATED', 'Product', $product->id, ['name' => $product->name, 'price' => $product->price, 'discount_percent' => $product->discount_percent, 'stock' => $product->stock]);

        return redirect()->route('seller.products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(int $product): View
    {
        $store = $this->sellerStore();

        return view('seller.products.edit', [
            'product' => $store->products()->findOrFail($product),
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $store = $this->sellerStore();
        $this->ensureStoreCanOperate($store);
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', 'active'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'discount_percent' => ['nullable', 'integer', 'between:0,100'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $product = $store->products()->findOrFail($product);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageOptimizer::store($request->file('image'), 'products');

            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        }

        $product->update($validated);
        AuditLogger::log('PRODUCT_UPDATED', 'Product', $product->id, ['name' => $product->name, 'price' => $product->price, 'discount_percent' => $product->discount_percent, 'stock' => $product->stock]);

        return redirect()->route('seller.products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(int $product): RedirectResponse
    {
        $store = $this->sellerStore();
        $product = $store->products()->findOrFail($product);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        AuditLogger::log('PRODUCT_DELETED', 'Product', $product->id, ['name' => $product->name]);

        return redirect()->route('seller.products.index')->with('status', 'Produk berhasil dihapus.');
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
