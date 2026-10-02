<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use App\Support\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function store(Request $request): JsonResponse
    {
        $this->ensureBuyer($request);
        $validated = $request->validate([
            'shipping_methods' => ['required', 'array'],
            'shipping_methods.*' => ['required', Rule::in(['seller_delivery', 'store_pickup'])],
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in([Payment::METHOD_BANK_TRANSFER, Payment::METHOD_QRIS, Payment::METHOD_COD])],
        ]);

        $order = $this->orders->createOrder($request->user(), $validated);
        $order->load(['items', 'sellerOrders.sellerProfile']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensureBuyer($request);

        $orders = Order::query()
            ->with(['items', 'sellerOrders.sellerProfile'])
            ->where('buyer_id', $request->user()->id)
            ->latest('id')
            ->paginate($request->integer('per_page', 15) ?: 15)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');

        $order->load([
            'items',
            'sellerOrders.sellerProfile',
            'sellerOrders.payments',
            'sellerOrders.shipment',
        ]);

        return new OrderResource($order);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');
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
            'source' => 'api',
        ]);

        foreach ($order->sellerOrders as $sellerOrder) {
            UserNotification::send(
                $sellerOrder->sellerProfile->user_id,
                'Pesanan dibatalkan',
                "Pesanan {$order->order_number} dibatalkan oleh pembeli.",
                'order_cancelled'
            );
        }

        return response()->json([
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => new OrderResource($order->load(['items', 'sellerOrders.sellerProfile'])),
        ]);
    }

    public function complete(Request $request, Order $order): JsonResponse
    {
        $this->ensureBuyer($request);
        abort_unless($order->buyer_id === $request->user()->id, 403, 'Pesanan bukan milik Anda.');

        $order->load('sellerOrders.sellerProfile');
        $completedCount = 0;

        foreach ($order->sellerOrders as $sellerOrder) {
            if ($sellerOrder->shipping_method === 'seller_delivery'
                && $sellerOrder->shipping_status === 'delivered'
                && $sellerOrder->status !== 'completed') {
                $sellerOrder->update(['status' => 'completed']);
                $sellerOrder->shipment()->update(['status' => 'completed', 'completed_at' => now()]);
                $completedCount++;

                AuditLogger::log('ORDER_COMPLETED', 'SellerOrder', $sellerOrder->id, [
                    'order_number' => $order->order_number,
                    'shipping_method' => 'seller_delivery',
                    'confirmed_by' => 'buyer',
                    'source' => 'api',
                ]);
                UserNotification::send(
                    $sellerOrder->sellerProfile->user_id,
                    'Pesanan selesai',
                    "Pesanan {$order->order_number} telah dikonfirmasi selesai oleh pembeli.",
                    'order_completed'
                );
            }
        }

        abort_if($completedCount === 0, 422, 'Tidak ada pesanan yang bisa dikonfirmasi selesai.');

        $order->refreshStatus();

        return response()->json([
            'message' => 'Pesanan dikonfirmasi selesai.',
            'data' => new OrderResource($order->load(['items', 'sellerOrders.sellerProfile'])),
        ]);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()->role === 'buyer', 403);
    }
}
