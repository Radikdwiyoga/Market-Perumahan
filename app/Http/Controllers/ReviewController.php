<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'buyer' && $order->buyer_id === auth()->id(), 403);
        abort_unless($order->status === 'completed', 422, 'Review tersedia setelah order selesai.');

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);
        $item = $order->items()->where('product_id', $validated['product_id'])->firstOrFail();
        abort_if(Review::query()->where(['buyer_id' => auth()->id(), 'order_id' => $order->id, 'product_id' => $item->product_id])->exists(), 422, 'Produk ini sudah direview.');

        Review::create([
            'buyer_id' => auth()->id(),
            'order_id' => $order->id,
            'product_id' => $item->product_id,
            'seller_profile_id' => $item->seller_profile_id,
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        return back()->with('status', 'Rating dan review berhasil disimpan.');
    }
}
