<?php

namespace App\Http\Controllers;

use App\Models\SellerProfile;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SellerApplicationController extends Controller
{
    public function create(): View
    {
        $user = auth()->user();
        $store = $user->sellerProfile()->first();
        abort_if($user->isAdmin(), 403);
        abort_if($store && ! $store->canSubmitVerification(), 422, 'Pengajuan toko Anda sudah diproses.');

        return view('seller.application.create', [
            'store' => $store,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $existingStore = $user->sellerProfile()->first();
        abort_if($user->isAdmin(), 403);
        abort_if($existingStore && ! $existingStore->canSubmitVerification(), 422, 'Pengajuan toko Anda sudah diproses.');

        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i', 'after:open_time'],
        ]);

        $store = DB::transaction(function () use ($user, $validated): SellerProfile {
            $store = $user->sellerProfile()->firstOrNew();
            $store->fill([
                ...$validated,
                'status' => 'closed',
                'verification_status' => 'pending',
                'rejection_reason' => null,
                'verified_at' => null,
                'verified_by' => null,
                'submitted_at' => now(),
            ]);

            if (! $store->exists) {
                $store->user_id = $user->id;
            }

            $store->save();

            // Role naik ke `seller` agar bisa masuk dashboard toko, tetapi `status`
            // milik pengelola: akun yang dinonaktifkan tidak diaktifkan ulang
            // lewat jalur ini.
            $user->update(['role' => 'seller']);

            return $store;
        });

        AuditLogger::log('SELLER_APPLICATION_SUBMITTED', 'SellerProfile', $store->id, ['store_name' => $store->store_name]);

        UserNotification::sendToAdmins('Pengajuan toko baru', "Toko {$store->store_name} menunggu verifikasi.", 'seller_verification_pending');

        return redirect()->route('dashboard')->with('status', 'Pengajuan toko terkirim. Pengelola akan meninjau data Anda.');
    }
}
