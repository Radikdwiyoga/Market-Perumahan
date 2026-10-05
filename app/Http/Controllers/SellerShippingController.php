<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerShippingController extends Controller
{
    public function edit(): View
    {
        return view('seller.shipping.edit', ['store' => $this->sellerStore()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $this->sellerStore();
        $this->ensureStoreCanOperate($store);
        $validated = $request->validate([
            'delivery_fee' => ['required', 'integer', 'min:0', 'max:1000000'],
            'min_order_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'free_shipping_threshold' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ]);

        $enableDelivery = $request->boolean('enable_delivery');
        $enablePickup = $request->boolean('enable_pickup');
        $freeShippingThreshold = $validated['free_shipping_threshold'] ?? null;

        if (! $enableDelivery && ! $enablePickup) {
            return back()->withErrors(['enable_delivery' => 'Aktifkan minimal satu metode pengiriman.'])->withInput();
        }

        if ($freeShippingThreshold && $freeShippingThreshold < $validated['min_order_amount']) {
            return back()->withErrors(['free_shipping_threshold' => 'Minimal belanja gratis ongkir tidak boleh lebih kecil dari minimal pembelian.'])->withInput();
        }

        $store->update([
            'enable_delivery' => $enableDelivery,
            'enable_pickup' => $enablePickup,
            'delivery_fee' => $validated['delivery_fee'],
            'min_order_amount' => $validated['min_order_amount'],
            'free_shipping_threshold' => $freeShippingThreshold,
        ]);

        AuditLogger::log('SHIPPING_SETTINGS_UPDATED', 'SellerProfile', $store->id, [
            'enable_delivery' => $store->enable_delivery,
            'enable_pickup' => $store->enable_pickup,
            'delivery_fee' => $store->delivery_fee,
            'min_order_amount' => $store->min_order_amount,
        ]);

        return redirect()->route('seller.shipping.edit')->with('status', 'Pengaturan pengiriman berhasil disimpan.');
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
