<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Buat atau perbarui baris pembayaran untuk satu sub-order.
     *
     * Pembeli memilih `bank_transfer` atau `qris` (lalu mengunggah bukti lewat
     * endpoint proof) maupun `cod`. Satu baris pembayaran dibuat per sub-order.
     */
    public function createForOrder(Request $request, Order $order): JsonResource|JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422, 'Pesanan tidak dapat dibayar lagi.');

        $validated = $request->validate([
            'seller_order_id' => ['required', 'integer', 'exists:seller_orders,id'],
            'method' => ['required', 'in:bank_transfer,qris,cod'],
        ]);

        $sellerOrder = $order->sellerOrders()->findOrFail($validated['seller_order_id']);
        abort_if($sellerOrder->status === 'cancelled', 422, 'Sub-order tidak dapat dibayar lagi.');

        $payment = $sellerOrder->payments()->latest('id')->first() ?? new Payment([
            'order_id' => $order->id,
            'seller_order_id' => $sellerOrder->id,
            'buyer_id' => $order->buyer_id,
            'seller_profile_id' => $sellerOrder->seller_profile_id,
        ]);

        // Pembayaran yang sudah lunas bersifat final: pembeli tidak boleh
        // mengulanginya untuk mengosongkan bukti, mengganti metode, atau
        // membatalkan verifikasi yang sudah dilakukan penjual/admin.
        abort_if($payment->status === Payment::STATUS_PAID, 422, 'Pembayaran untuk sub-order ini sudah lunas.');

        // QRIS memerlukan gambar QRIS toko; pakai snapshot agar histori tetap utuh.
        $qrisSnapshot = null;
        if ($validated['method'] === Payment::METHOD_QRIS) {
            $qrisImage = $sellerOrder->sellerProfile->paymentSetting?->qris_image;
            abort_if($qrisImage === null, 422, 'Toko ini belum mengunggah QRIS.');
            $qrisSnapshot = $qrisImage;
        }

        if ($payment->proof_image) {
            Storage::disk('public')->delete($payment->proof_image);
        }

        $payment->fill([
            'method' => $validated['method'],
            'amount' => $sellerOrder->total_amount,
            'status' => Payment::STATUS_PENDING,
            'proof_image' => null,
            'qris_image_snapshot' => $qrisSnapshot,
            'rejection_reason' => null,
            'paid_at' => null,
            'verified_at' => null,
        ]);
        $payment->save();

        $sellerOrder->update([
            'payment_status' => Payment::STATUS_PENDING,
            // Batas bayar selalu diset ulang agar `orders:cancel-expired` tetap
            // bisa membatalkan sub-order yang tidak pernah dibayar.
            'payment_due_at' => $validated['method'] === Payment::METHOD_COD
                ? null
                : now()->addMinutes((int) config('marketplace.payment_expiry_minutes', 15)),
        ]);
        $order->refreshStatus();

        AuditLogger::log('PAYMENT_CREATED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'method' => $payment->method,
            'amount' => $sellerOrder->total_amount,
            'source' => 'api',
        ]);

        return (new PaymentResource($payment->load(['sellerOrder.order', 'sellerOrder.sellerProfile'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Unggah bukti pembayaran untuk transfer bank / QRIS.
     */
    public function uploadProof(Request $request, Payment $payment): PaymentResource
    {
        abort_unless($payment->buyer_id === $request->user()->id, 403);
        abort_unless($payment->requiresProof(), 422, 'Bukti pembayaran tidak diperlukan untuk COD.');

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($payment->proof_image) {
            Storage::disk('public')->delete($payment->proof_image);
        }

        $payment->update(['proof_image' => $validated['proof_image']->store('payment-proofs', 'public')]);

        return new PaymentResource($payment->refresh()->load(['sellerOrder.order', 'sellerOrder.sellerProfile']));
    }

    public function show(Request $request, Payment $payment): PaymentResource
    {
        $this->authorizeView($request, $payment);

        return new PaymentResource($payment->load(['sellerOrder.order', 'sellerOrder.sellerProfile']));
    }

    public function verify(Request $request, Payment $payment): PaymentResource
    {
        $this->authorizeVerify($request, $payment);

        // Hanya pembayaran `pending` yang boleh diputuskan. Yang sudah lunas
        // bersifat final, dan yang ditolak (admin/kedaluwarsa) tidak boleh
        // diaktifkan kembali: pembeli harus membuat pembayaran baru.
        abort_unless($payment->isVerifiable(), 422, 'Pembayaran ini sudah lunas atau ditolak.');

        $payment->update(['status' => Payment::STATUS_PAID, 'paid_at' => now(), 'verified_at' => now()]);
        $payment->sellerOrder()->update(['payment_status' => Payment::STATUS_PAID, 'status' => 'processing']);
        $payment->sellerOrder->order->refreshStatus();

        $order = $payment->sellerOrder->order;
        $by = $request->user()->isAdmin() ? 'admin' : 'seller';
        AuditLogger::log('PAYMENT_VERIFIED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'method' => $payment->method,
            'amount' => $payment->amount,
            'by' => $by,
            'source' => 'api',
        ]);
        UserNotification::send($payment->buyer_id, 'Pembayaran diterima', "Pembayaran pesanan {$order->order_number} telah diterima dan pesanan sedang diproses.", 'payment_verified');
        if ($by === 'admin') {
            UserNotification::send($payment->sellerOrder->sellerProfile->user_id, 'Pembayaran diverifikasi', "Pembayaran pesanan {$order->order_number} telah diverifikasi.", 'payment_verified');
        }

        return new PaymentResource($payment->refresh()->load(['sellerOrder.order', 'sellerOrder.sellerProfile']));
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        $this->authorizeVerify($request, $payment);
        abort_unless($payment->isVerifiable(), 422, 'Pembayaran ini sudah lunas atau ditolak.');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment->update(['status' => Payment::STATUS_FAILED, 'rejection_reason' => $validated['rejection_reason']]);
        $payment->sellerOrder()->update(['payment_status' => Payment::STATUS_FAILED]);

        $order = $payment->sellerOrder->order;
        $order->refreshStatus();
        AuditLogger::log('PAYMENT_REJECTED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'reason' => $validated['rejection_reason'],
            'source' => 'api',
        ]);
        UserNotification::send($payment->buyer_id, 'Pembayaran ditolak', "Pembayaran pesanan {$order->order_number} ditolak: {$validated['rejection_reason']}.", 'payment_rejected');

        return response()->json([
            'message' => 'Pembayaran ditolak.',
            'data' => new PaymentResource($payment->refresh()->load(['sellerOrder.order', 'sellerOrder.sellerProfile'])),
        ]);
    }

    private function authorizeView(Request $request, Payment $payment): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isSeller()) {
            $store = $user->sellerProfile()->first();
            abort_unless($store && $payment->seller_profile_id === $store->id, 404);

            return;
        }

        abort_unless($user->role === 'buyer' && $payment->buyer_id === $user->id, 403);
    }

    private function authorizeVerify(Request $request, Payment $payment): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isSeller()) {
            $store = $user->sellerProfile()->first();
            abort_unless($store && $payment->seller_profile_id === $store->id, 404);

            return;
        }

        abort(403);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()->role === 'buyer', 403);
    }
}
