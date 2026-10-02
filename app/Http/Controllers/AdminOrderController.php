<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $status = $request->string('status')->value();

        return view('admin.orders.index', [
            'orders' => Order::query()
                ->with(['buyer', 'sellerOrders.sellerProfile', 'sellerOrders.payments'])
                ->when(in_array($status, ['pending', 'processing', 'completed', 'cancelled'], true), fn ($query) => $query->where('status', $status))
                ->latest()
                ->get(),
            'status' => $status,
        ]);
    }

    public function verifyPayment(Payment $payment): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($payment->requiresManualVerification(), 422, 'Pembayaran ini sudah lunas.');

        $payment->update(['status' => 'paid', 'paid_at' => now(), 'verified_at' => now()]);
        $payment->sellerOrder()->update(['payment_status' => 'paid', 'status' => 'processing']);
        $payment->sellerOrder->order()->update(['status' => 'processing']);

        $order = $payment->sellerOrder->order;
        AuditLogger::log('PAYMENT_VERIFIED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'method' => $payment->method,
            'amount' => $payment->amount,
            'by' => 'admin',
        ]);
        UserNotification::send($payment->buyer_id, 'Pembayaran diterima', "Pembayaran pesanan {$order->order_number} telah diterima dan pesanan sedang diproses.", 'payment_verified');
        UserNotification::send($payment->sellerOrder->sellerProfile->user_id, 'Pembayaran diverifikasi', "Pembayaran pesanan {$order->order_number} telah diverifikasi.", 'payment_verified');

        return back()->with('status', 'Pembayaran berhasil diverifikasi oleh admin.');
    }

    public function rejectPayment(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($payment->requiresManualVerification(), 422, 'Pembayaran ini sudah lunas.');
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment->update(['status' => 'failed', 'rejection_reason' => $validated['rejection_reason']]);
        $payment->sellerOrder()->update(['payment_status' => 'failed']);

        $order = $payment->sellerOrder->order;
        AuditLogger::log('PAYMENT_REJECTED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'reason' => $validated['rejection_reason'],
        ]);
        UserNotification::send($payment->buyer_id, 'Pembayaran ditolak', "Pembayaran pesanan {$order->order_number} ditolak: {$validated['rejection_reason']}.", 'payment_rejected');

        return back()->with('status', 'Pembayaran ditolak.');
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}
