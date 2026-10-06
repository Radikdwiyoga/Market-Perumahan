<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SellerOrderController extends Controller
{
    private const DELIVERY_STATUSES = ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered'];

    private const PICKUP_STATUSES = ['pending', 'processing', 'ready'];

    private const FILTER_STATUSES = ['pending', 'processing', 'completed', 'cancelled'];

    public function index(Request $request): View
    {
        $store = $this->sellerStore();
        $status = $request->string('status')->value();
        $search = $request->string('q')->trim()->value();

        $sellerOrders = SellerOrder::query()
            ->with(['order.buyer', 'payments', 'shipment'])
            ->where('seller_profile_id', $store->id)
            ->when(in_array($status, self::FILTER_STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->whereHas(
                'order',
                fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('buyer', fn ($buyerQuery) => $buyerQuery->where('name', 'like', "%{$search}%"))
            ))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('seller.orders.index', [
            'store' => $store,
            'sellerOrders' => $sellerOrders,
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function show(SellerOrder $sellerOrder): View
    {
        $this->authorizeSellerOrder($sellerOrder);

        $sellerOrder->load(['order.buyer', 'order.items.product', 'payments', 'shipment']);

        return view('seller.orders.show', [
            'store' => $this->sellerStore(),
            'sellerOrder' => $sellerOrder,
            'items' => $sellerOrder->order->items->where('seller_profile_id', $sellerOrder->seller_profile_id)->values(),
            'allowedShippingStatuses' => $sellerOrder->shipping_method === 'store_pickup' ? self::PICKUP_STATUSES : self::DELIVERY_STATUSES,
        ]);
    }

    public function verifyPayment(Payment $payment): RedirectResponse
    {
        $store = $this->sellerStore();
        abort_unless($payment->seller_profile_id === $store->id, 404);

        // Hanya pembayaran `pending` yang boleh diputuskan. Yang sudah lunas
        // bersifat final, dan yang ditolak (admin/kedaluwarsa) tidak boleh
        // diaktifkan kembali: pembeli harus membuat pembayaran baru.
        abort_unless($payment->isVerifiable(), 422, 'Pembayaran ini sudah lunas atau ditolak.');
        abort_unless($payment->hasRequiredProof(), 422, 'Bukti pembayaran wajib diunggah sebelum verifikasi.');

        $payment->update(['status' => Payment::STATUS_PAID, 'paid_at' => now(), 'verified_at' => now()]);
        $payment->sellerOrder()->update(['payment_status' => Payment::STATUS_PAID, 'status' => 'processing']);
        $payment->sellerOrder->order->refreshStatus();

        $order = $payment->sellerOrder->order;
        AuditLogger::log('PAYMENT_VERIFIED', 'Payment', $payment->id, [
            'order_number' => $order->order_number,
            'method' => $payment->method,
            'amount' => $payment->amount,
            'by' => 'seller',
        ]);
        UserNotification::send($payment->buyer_id, 'Pembayaran diterima', "Pembayaran pesanan {$order->order_number} telah diterima dan pesanan sedang diproses.", 'payment_verified');

        return redirect()->route('seller.orders.show', $payment->sellerOrder)->with('status', 'Pembayaran berhasil diverifikasi.');
    }

    public function updateShipping(Request $request, SellerOrder $sellerOrder): RedirectResponse
    {
        $this->authorizeSellerOrder($sellerOrder);

        $allowed = $sellerOrder->shipping_method === 'store_pickup' ? self::PICKUP_STATUSES : self::DELIVERY_STATUSES;
        $status = $request->validate([
            'shipping_status' => ['required', Rule::in($allowed)],
        ])['shipping_status'];
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diproses.');

        $oldStatus = $sellerOrder->shipping_status;
        $sellerOrder->update([
            'shipping_status' => $status,
            'status' => $sellerOrder->status === 'cancelled' ? 'cancelled' : 'processing',
        ]);

        $shipment = $sellerOrder->shipment()->first();
        if ($shipment) {
            $shipment->update([
                'status' => $status,
                'delivered_at' => $status === 'delivered' ? now() : $shipment->delivered_at,
            ]);
        }

        $sellerOrder->order->refreshStatus();

        AuditLogger::log('SHIPPING_UPDATED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'from' => $oldStatus,
            'to' => $status,
        ]);

        $buyerId = $sellerOrder->order->buyer_id;
        $orderNumber = $sellerOrder->order->order_number;
        if ($status === 'ready') {
            $body = $sellerOrder->shipping_method === 'store_pickup'
                ? "Pesanan {$orderNumber} siap diambil di toko. Kode pengambilan: {$sellerOrder->pickup_code}."
                : "Pesanan {$orderNumber} sudah siap dan akan segera diantar.";
            UserNotification::send($buyerId, 'Pesanan siap', $body, 'order_ready');
        } elseif ($status === 'out_for_delivery') {
            UserNotification::send($buyerId, 'Pesanan diantar', "Pesanan {$orderNumber} sedang dalam perjalanan ke alamat Anda.", 'order_out_for_delivery');
        } elseif ($status === 'delivered') {
            UserNotification::send($buyerId, 'Pesanan dikirim', "Pesanan {$orderNumber} sudah sampai. Silakan konfirmasi penerimaan di halaman pesanan.", 'order_delivered');
        }

        return redirect()->route('seller.orders.show', $sellerOrder)->with('status', 'Status pengiriman berhasil diperbarui.');
    }

    public function verifyPickup(Request $request, SellerOrder $sellerOrder): RedirectResponse
    {
        $this->authorizeSellerOrder($sellerOrder);
        abort_if($sellerOrder->shipping_method !== 'store_pickup', 422, 'Metode pengambilan tidak sesuai.');
        abort_if($sellerOrder->shipping_status !== 'ready', 422, 'Pesanan belum siap diambil.');
        abort_if($sellerOrder->status === 'cancelled', 422, 'Pesanan sudah dibatalkan.');
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diserahkan.');
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diserahkan.');

        $code = strtoupper((string) $request->string('pickup_code')->value());
        abort_if($code === '' || ! hash_equals((string) $sellerOrder->pickup_code, $code), 422, 'Kode pengambilan salah.');

        // `payment_status` tidak dipaksa menjadi `paid` di sini: untuk transfer
        // bank/QRIS uang baru benar-benar masuk setelah verifikasi manual. COD
        // tetap ditandai lunas di bawah sesuai alur COD.
        $sellerOrder->update(['shipping_status' => 'completed', 'status' => 'completed']);
        $sellerOrder->shipment()->update(['status' => 'completed', 'completed_at' => now()]);

        $sellerOrder->settleCodPayment();

        $sellerOrder->order->refreshStatus();

        AuditLogger::log('ORDER_COMPLETED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'shipping_method' => 'store_pickup',
        ]);
        UserNotification::send($sellerOrder->order->buyer_id, 'Pesanan selesai', "Pesanan {$sellerOrder->order->order_number} selesai. Terima kasih telah berbelanja.", 'order_completed');

        return redirect()->route('seller.orders.show', $sellerOrder)->with('status', 'Kode valid, pesanan berhasil diambil.');
    }

    private function authorizeSellerOrder(SellerOrder $sellerOrder): void
    {
        abort_unless($sellerOrder->seller_profile_id === $this->sellerStore()->id, 404);
    }

    private function sellerStore(): SellerProfile
    {
        abort_unless(auth()->user()->isSeller(), 403);

        return auth()->user()->sellerProfile()->firstOrFail();
    }
}
