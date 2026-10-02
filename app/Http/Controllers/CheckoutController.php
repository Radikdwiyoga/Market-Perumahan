<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\CartService;
use App\Support\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $carts, protected OrderService $orders) {}

    public function create(Request $request): View
    {
        $this->ensureBuyer();
        $buyer = auth()->user();
        $products = $this->carts->products($buyer);

        abort_if($products->isEmpty(), 404, 'Keranjang masih kosong.');

        $quantities = $this->carts->quantities($buyer);
        $sellerGroups = $products->groupBy('seller_profile_id')->map(function ($sellerProducts) use ($quantities): array {
            $store = $sellerProducts->first()->sellerProfile;
            $subtotal = $sellerProducts->sum(fn ($product): int => $product->effectivePrice() * $quantities[$product->id]);

            return [
                'store' => $store,
                'products' => $sellerProducts,
                'subtotal' => $subtotal,
                'methods' => collect($store->availableShippingMethods())->map(fn (string $method): array => [
                    'value' => $method,
                    'label' => $method === 'seller_delivery' ? 'Diantar penjual' : 'Ambil di toko',
                    'fee' => $store->shippingFeeFor($method, $subtotal),
                ])->values()->all(),
            ];
        });

        return view('checkout.create', [
            'products' => $products,
            'sellerGroups' => $sellerGroups,
            'quantities' => $quantities,
            'subtotal' => $this->carts->subtotal($buyer),
            'shippingFee' => $sellerGroups->sum(fn (array $group): int => $group['methods'][0]['fee'] ?? 0),
            'buyer' => $buyer,
            'qrisAvailable' => $sellerGroups->every(fn (array $group): bool => $group['store']->paymentSetting?->qris_image !== null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureBuyer();
        $validated = $request->validate([
            'shipping_methods' => ['required', 'array'],
            'shipping_methods.*' => ['required', Rule::in(['seller_delivery', 'store_pickup'])],
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in([Payment::METHOD_BANK_TRANSFER, Payment::METHOD_QRIS, Payment::METHOD_COD])],
        ]);

        $order = $this->orders->createOrder(auth()->user(), $validated);

        return redirect()->route('orders.show', $order)->with('status', 'Pesanan berhasil dibuat. Selesaikan pembayaran untuk memproses pesanan.');
    }

    private function ensureBuyer(): void
    {
        abort_unless(auth()->user()->role === 'buyer', 403);
    }
}
