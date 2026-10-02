<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerOrder;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $this->ensureBuyer();

        return view('orders.index', [
            'orders' => Order::query()
                ->with('sellerOrders.sellerProfile')
                ->where('buyer_id', auth()->id())
                ->latest()
                ->get(),
        ]);
    }

    public function show(Order $order): View
    {
        $this->ensureBuyer();
        abort_unless($order->buyer_id === auth()->id(), 403);

        $order->load(['items.product.reviews', 'sellerOrders.sellerProfile', 'sellerOrders.payments', 'sellerOrders.shipment']);

        return view('orders.show', [
            'order' => $order,
            'canCancel' => $order->status !== 'cancelled' && $order->status !== 'completed' && $order->sellerOrders->every(
                fn (SellerOrder $sellerOrder): bool => $sellerOrder->status === 'pending' && $sellerOrder->payment_status !== 'paid'
            ),
            'reviewedProductIds' => Review::query()->where('buyer_id', auth()->id())->where('order_id', $order->id)->pluck('product_id')->all(),
        ]);
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->ensureBuyer();
        abort_unless($order->buyer_id === auth()->id(), 403);
        abort_if(in_array($order->status, ['completed', 'cancelled'], true), 422, 'Pesanan tidak dapat dibatalkan.');

        $order->load('sellerOrders.sellerProfile');
        abort_if(
            $order->sellerOrders->contains(fn (SellerOrder $sellerOrder): bool => $sellerOrder->status !== 'pending' || $sellerOrder->payment_status === 'paid'),
            422,
            'Pesanan sudah diproses penjual dan tidak dapat dibatalkan.'
        );

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            foreach ($order->sellerOrders as $sellerOrder) {
                $sellerOrder->payments()->update(['status' => 'failed']);
                $sellerOrder->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            }

            $order->refreshStatus();
        });

        AuditLogger::log('ORDER_CANCELLED', 'Order', $order->id, [
            'order_number' => $order->order_number,
            'reason' => 'buyer_request',
        ]);

        foreach ($order->sellerOrders as $sellerOrder) {
            UserNotification::send(
                $sellerOrder->sellerProfile->user_id,
                'Pesanan dibatalkan',
                "Pesanan {$order->order_number} dibatalkan oleh pembeli.",
                'order_cancelled'
            );
        }

        return redirect()->route('orders.show', $order)->with('status', 'Pesanan berhasil dibatalkan.');
    }

    public function confirmReceived(SellerOrder $sellerOrder): RedirectResponse
    {
        $this->ensureBuyer();
        abort_unless($sellerOrder->order->buyer_id === auth()->id(), 403);
        abort_unless($sellerOrder->shipping_method === 'seller_delivery', 422, 'Metode pengiriman tidak sesuai.');
        abort_if($sellerOrder->shipping_status !== 'delivered', 422, 'Pesanan belum selesai dikirim.');
        abort_if($sellerOrder->status === 'completed', 422, 'Pesanan sudah selesai.');

        $sellerOrder->update(['status' => 'completed']);
        $sellerOrder->shipment()->update(['status' => 'completed', 'completed_at' => now()]);
        $sellerOrder->order->refreshStatus();

        AuditLogger::log('ORDER_COMPLETED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'shipping_method' => 'seller_delivery',
            'confirmed_by' => 'buyer',
        ]);
        UserNotification::send($sellerOrder->sellerProfile->user_id, 'Pesanan selesai', "Pesanan {$sellerOrder->order->order_number} telah dikonfirmasi selesai oleh pembeli.", 'order_completed');

        return back()->with('status', 'Terima kasih, pesanan dikonfirmasi telah diterima.');
    }

    private function ensureBuyer(): void
    {
        abort_unless(auth()->user()->role === 'buyer', 403);
    }
}
