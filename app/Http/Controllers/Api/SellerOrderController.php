<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerOrderResource;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SellerOrderController extends Controller
{
    private const ORDER_STATUSES = ['pending', 'processing', 'completed', 'cancelled'];

    private const DELIVERY_STATUSES = ['pending', 'processing', 'ready', 'out_for_delivery', 'delivered'];

    private const PICKUP_STATUSES = ['pending', 'processing', 'ready'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $store = $this->sellerStore($request);
        $status = $request->string('status')->value();
        $search = $request->string('q')->trim()->value();

        $sellerOrders = SellerOrder::query()
            ->with(['order.buyer', 'order.items', 'sellerProfile', 'payments'])
            ->where('seller_profile_id', $store->id)
            ->when(in_array($status, self::ORDER_STATUSES, true), fn ($query) => $query->where('status', $status))
            ->when($search, fn ($query) => $query->whereHas(
                'order',
                fn ($orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('buyer', fn ($buyerQuery) => $buyerQuery->where('name', 'like', "%{$search}%"))
            ))
            ->latest('id')
            ->paginate($request->integer('per_page', 15) ?: 15)
            ->withQueryString();

        return SellerOrderResource::collection($sellerOrders);
    }

    public function show(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        $store = $this->sellerStore($request);
        abort_unless($sellerOrder->seller_profile_id === $store->id, 404);

        return new SellerOrderResource($this->loadForResource($sellerOrder));
    }

    public function accept(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        $this->ensureOwnOrder($request, $sellerOrder);
        abort_if($sellerOrder->status !== 'pending', 422, 'Order tidak dalam status menunggu.');
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diproses.');

        $sellerOrder->update(['status' => 'processing']);
        $sellerOrder->order->refreshStatus();

        AuditLogger::log('SELLER_ORDER_ACCEPTED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'source' => 'api',
        ]);
        UserNotification::send(
            $sellerOrder->order->buyer_id,
            'Pesanan diterima',
            "Pesanan {$sellerOrder->order->order_number} telah diterima dan sedang diproses.",
            'order_processing'
        );

        return new SellerOrderResource($this->loadForResource($sellerOrder));
    }

    public function process(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        $this->ensureOwnOrder($request, $sellerOrder);
        abort_if(! in_array($sellerOrder->status, ['pending', 'processing'], true), 422, 'Order tidak dapat diproses.');
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diproses.');

        $sellerOrder->update(['status' => 'processing']);
        $sellerOrder->order->refreshStatus();

        AuditLogger::log('SELLER_ORDER_PROCESSED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'source' => 'api',
        ]);

        return new SellerOrderResource($this->loadForResource($sellerOrder));
    }

    public function ready(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        return $this->transitionShipping($request, $sellerOrder, 'ready');
    }

    public function outForDelivery(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        return $this->transitionShipping($request, $sellerOrder, 'out_for_delivery');
    }

    public function delivered(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        return $this->transitionShipping($request, $sellerOrder, 'delivered');
    }

    public function complete(Request $request, SellerOrder $sellerOrder): SellerOrderResource
    {
        $this->ensureOwnOrder($request, $sellerOrder);
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diselesaikan.');

        if ($sellerOrder->shipping_method === 'store_pickup') {
            abort_unless($sellerOrder->shipping_status === 'completed', 422, 'Pesanan pickup belum selesai diverifikasi.');
        } else {
            abort_unless($sellerOrder->shipping_status === 'delivered', 422, 'Pesanan belum sampai ke pembeli.');

            if ($sellerOrder->status !== 'completed') {
                $sellerOrder->update(['status' => 'completed']);
                $sellerOrder->shipment()->update(['status' => 'completed', 'completed_at' => now()]);
                $sellerOrder->settleCodPayment();
                $sellerOrder->order->refreshStatus();

                AuditLogger::log('ORDER_COMPLETED', 'SellerOrder', $sellerOrder->id, [
                    'order_number' => $sellerOrder->order->order_number,
                    'shipping_method' => 'seller_delivery',
                    'confirmed_by' => 'seller',
                    'source' => 'api',
                ]);
                UserNotification::send(
                    $sellerOrder->order->buyer_id,
                    'Pesanan selesai',
                    "Pesanan {$sellerOrder->order->order_number} telah diselesaikan oleh penjual.",
                    'order_completed'
                );
            }
        }

        return new SellerOrderResource($this->loadForResource($sellerOrder));
    }

    private function transitionShipping(Request $request, SellerOrder $sellerOrder, string $target): SellerOrderResource
    {
        $this->ensureOwnOrder($request, $sellerOrder);

        $allowed = $sellerOrder->shipping_method === 'store_pickup' ? self::PICKUP_STATUSES : self::DELIVERY_STATUSES;
        abort_unless(in_array($target, $allowed, true), 422, 'Transisi status pengiriman tidak valid.');
        abort_unless($sellerOrder->canBeFulfilled(), 422, 'Pembayaran harus lunas sebelum pesanan diproses.');

        $oldStatus = $sellerOrder->shipping_status;
        $sellerOrder->update([
            'shipping_status' => $target,
            'status' => $sellerOrder->status === 'cancelled' ? 'cancelled' : 'processing',
        ]);

        if ($shipment = $sellerOrder->shipment()->first()) {
            $shipment->update([
                'status' => $target,
                'delivered_at' => $target === 'delivered' ? now() : $shipment->delivered_at,
            ]);
        }

        $sellerOrder->order->refreshStatus();

        AuditLogger::log('SHIPPING_UPDATED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'from' => $oldStatus,
            'to' => $target,
            'source' => 'api',
        ]);

        $buyerId = $sellerOrder->order->buyer_id;
        $orderNumber = $sellerOrder->order->order_number;
        if ($target === 'ready') {
            $body = $sellerOrder->shipping_method === 'store_pickup'
                ? "Pesanan {$orderNumber} siap diambil di toko. Kode pengambilan: {$sellerOrder->pickup_code}."
                : "Pesanan {$orderNumber} sudah siap dan akan segera diantar.";
            UserNotification::send($buyerId, 'Pesanan siap', $body, 'order_ready');
        } elseif ($target === 'out_for_delivery') {
            UserNotification::send($buyerId, 'Pesanan diantar', "Pesanan {$orderNumber} sedang dalam perjalanan ke alamat Anda.", 'order_out_for_delivery');
        } elseif ($target === 'delivered') {
            UserNotification::send($buyerId, 'Pesanan dikirim', "Pesanan {$orderNumber} sudah sampai. Silakan konfirmasi penerimaan di halaman pesanan.", 'order_delivered');
        }

        return new SellerOrderResource($this->loadForResource($sellerOrder));
    }

    private function ensureOwnOrder(Request $request, SellerOrder $sellerOrder): void
    {
        $store = $this->sellerStore($request);
        abort_unless($sellerOrder->seller_profile_id === $store->id, 404);
    }

    private function loadForResource(SellerOrder $sellerOrder): SellerOrder
    {
        return $sellerOrder->load(['order.buyer', 'order.items', 'sellerProfile', 'payments', 'shipment']);
    }

    private function sellerStore(Request $request): SellerProfile
    {
        abort_unless($request->user()->isSeller(), 403);
        $store = $request->user()->sellerProfile()->first();

        return $store ?? abort(403, 'Toko tidak ditemukan.');
    }
}
