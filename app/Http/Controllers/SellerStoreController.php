<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use App\Support\AuditLogger;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SellerStoreController extends Controller
{
    public function edit(): View
    {
        return view('seller.store.edit', ['store' => $this->sellerStore()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $store = $this->sellerStore();
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i', 'after:open_time'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageOptimizer::store($request->file('image'), 'stores');

            if ($store->image) {
                Storage::disk('public')->delete($store->image);
            }
        }

        // Status toko hanya boleh diubah seller yang tokonya sudah terverifikasi,
        // sehingga seller tidak bisa membuka sendiri toko yang masih ditinjau
        // atau yang ditangguhkan pengelola.
        if ($request->has('status')) {
            $status = $request->validate(['status' => ['required', 'in:open,closed']])['status'];

            if ($status === 'open') {
                $this->ensureStoreCanOperate($store);
            }

            $validated['status'] = $status;
        }

        $store->update($validated);
        AuditLogger::log('STORE_UPDATED', 'SellerProfile', $store->id, ['store_name' => $store->store_name, 'status' => $store->status]);

        return redirect()->route('seller.store.edit')->with('status', 'Profil toko berhasil diperbarui.');
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
