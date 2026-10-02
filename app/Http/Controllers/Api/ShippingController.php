<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');

        $order->load(['sellerOrders.sellerProfile', 'sellerOrders.shipment']);

        return response()->json([
            'order_number' => $order->order_number,
            'status' => $order->status,
            'orders' => $order->sellerOrders->map(fn (SellerOrder $sellerOrder): array => [
                'id' => $sellerOrder->id,
                'store' => $sellerOrder->sellerProfile?->store_name,
                'shipping_method' => $sellerOrder->shipping_method,
                'shipping_status' => $sellerOrder->shipping_status,
                'status' => $sellerOrder->status,
                'pickup_code' => $sellerOrder->pickup_code,
                'shipment' => $sellerOrder->shipment->first() ? array_filter([
                    'method' => $sellerOrder->shipment->first()->method,
                    'address' => $sellerOrder->shipment->first()->address,
                    'recipient_name' => $sellerOrder->shipment->first()->recipient_name,
                    'recipient_phone' => $sellerOrder->shipment->first()->recipient_phone,
                    'shipping_fee' => $sellerOrder->shipment->first()->shipping_fee,
                    'status' => $sellerOrder->shipment->first()->status,
                ], fn ($value) => $value !== null) : null,
            ]),
        ]);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');
        abort_if($order->status !== 'pending', 422, 'Alamat hanya dapat diubah selama pesanan masih menunggu.');

        $validated = $request->validate([
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:500'],
        ]);

        $address = trim((string) ($validated['shipping_address'] ?? ''));
        if ($address !== '') {
            $order->load('sellerOrders.shipment');
            $order->sellerOrders
                ->where('shipping_method', 'seller_delivery')
                ->each(fn (SellerOrder $sellerOrder) => $sellerOrder->shipment()->update(['address' => $address]));
        }

        AuditLogger::log('SHIPPING_UPDATED', 'Order', $order->id, [
            'order_number' => $order->order_number,
            'source' => 'api',
        ]);

        return response()->json(['message' => 'Alamat pengiriman berhasil diperbarui.']);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()->role === 'buyer', 403);
    }
}
