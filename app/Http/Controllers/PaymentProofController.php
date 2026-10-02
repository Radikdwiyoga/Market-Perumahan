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

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($payment->proof_image) {
            Storage::disk('public')->delete($payment->proof_image);
        }

        $payment->update([
            'proof_image' => $validated['proof_image']->store('payment-proofs', 'public'),
        ]);

        return back()->with('status', 'Bukti pembayaran berhasil diunggah dan menunggu verifikasi.');
    }
}
