<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentProofController extends Controller
{
    public function store(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->buyer_id === auth()->id(), 403);
        abort_unless($payment->requiresProof(), 422, 'Bukti pembayaran tidak diperlukan untuk COD.');
        abort_unless(in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_FAILED], true), 422, 'Pembayaran ini tidak dapat menerima bukti baru.');

        $sellerOrder = $payment->sellerOrder;
        abort_if(in_array($sellerOrder->status, ['completed', 'cancelled'], true), 422, 'Pesanan ini sudah selesai atau dibatalkan.');

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $oldProof = $payment->proof_image;
        $newProof = $validated['proof_image']->store('payment-proofs', 'public');
        $wasRejected = $payment->status === Payment::STATUS_FAILED;

        $payment->update([
            'proof_image' => $newProof,
            'status' => Payment::STATUS_PENDING,
            'rejection_reason' => null,
            'paid_at' => null,
            'verified_at' => null,
        ]);

        if ($wasRejected) {
            $sellerOrder->update([
                'payment_status' => Payment::STATUS_PENDING,
                'payment_due_at' => now()->addMinutes((int) config('marketplace.payment_expiry_minutes', 15)),
            ]);
        }

        if ($oldProof && $oldProof !== $newProof) {
            Storage::disk('public')->delete($oldProof);
        }

        return back()->with('status', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi.');
    }
}
