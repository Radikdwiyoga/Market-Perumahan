<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerPaymentSettingResource;
use App\Models\SellerPaymentSetting;
use App\Models\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SellerPaymentController extends Controller
{
    public function show(): SellerPaymentSettingResource
    {
        $setting = $this->sellerStore()->paymentSetting;

        return new SellerPaymentSettingResource($setting ?? new SellerPaymentSetting);
    }

    public function update(Request $request): JsonResponse
    {
        $store = $this->sellerStore();
        $setting = $store->paymentSetting ?? new SellerPaymentSetting(['seller_profile_id' => $store->id]);
        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
        ]);

        $setting->fill($validated);
        $setting->save();

        // PUT bersifat idempoten: selalu 200, walau barisnya baru dibuat.
        return (new SellerPaymentSettingResource($setting))->response()->setStatusCode(200);
    }

    public function storeQris(Request $request): SellerPaymentSettingResource
    {
        $validated = $request->validate([
            'qris_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $store = $this->sellerStore();
        $setting = $store->paymentSetting ?? new SellerPaymentSetting(['seller_profile_id' => $store->id]);

        if ($setting->qris_image) {
            Storage::disk('public')->delete($setting->qris_image);
        }

        $setting->fill([
            'qris_image' => $validated['qris_image']->store('qris', 'public'),
            'qris_status' => 'active',
            'qris_uploaded_at' => now(),
        ]);
        $setting->save();

        return new SellerPaymentSettingResource($setting);
    }

    public function destroyQris(): JsonResponse
    {
        $setting = $this->sellerStore()->paymentSetting;

        if ($setting?->qris_image) {
            Storage::disk('public')->delete($setting->qris_image);
            $setting->update(['qris_image' => null, 'qris_status' => 'inactive', 'qris_uploaded_at' => null]);
        }

        return response()->json(['message' => 'QRIS berhasil dihapus.']);
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
