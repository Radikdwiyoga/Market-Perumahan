<?php

namespace App\Http\Controllers;

use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SellerPaymentController extends Controller
{
    public function edit(): View
    {
        $store = $this->sellerStore();

        return view('seller.payment.edit', [
            'store' => $store,
            'setting' => $store->paymentSetting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $this->sellerStore();
        $setting = $store->paymentSetting ?? new SellerPaymentSetting(['seller_profile_id' => $store->id]);
        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('qris_image')) {
            if ($setting->qris_image) {
                Storage::disk('public')->delete($setting->qris_image);
            }

            $validated['qris_image'] = $request->file('qris_image')->store('qris', 'public');
            $validated['qris_status'] = 'active';
            $validated['qris_uploaded_at'] = now();
        }

        $setting->fill($validated);
        $setting->save();

        return redirect()->route('seller.payment.edit')->with('status', 'Pengaturan pembayaran berhasil disimpan.');
    }

    public function destroyQris(): RedirectResponse
    {
        $setting = $this->sellerStore()->paymentSetting;

        if ($setting?->qris_image) {
            Storage::disk('public')->delete($setting->qris_image);
            $setting->update(['qris_image' => null, 'qris_status' => 'inactive', 'qris_uploaded_at' => null]);
        }

        return back()->with('status', 'QRIS berhasil dihapus.');
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
