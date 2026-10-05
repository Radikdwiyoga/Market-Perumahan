<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SellerOrder;
use App\Models\SellerProfile;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupController extends Controller
{
    public function info(Request $request, SellerOrder $sellerOrder): JsonResponse
    {
        $store = $this->sellerStore($request);
        abort_unless($sellerOrder->seller_profile_id === $store->id, 404);
        abort_if($sellerOrder->shipping_method !== 'store_pickup', 422, 'Pesanan ini bukan metode ambil di toko.');

        return response()->json([
            'id' => $sellerOrder->id,
            'order_number' => $sellerOrder->order->order_number,
            'store' => ['id' => $store->id, 'store_name' => $store->store_name, 'address' => $store->address],
            'buyer' => ['name' => $sellerOrder->order->buyer->name, 'phone' => $sellerOrder->order->buyer->phone],
            'pickup_code' => $sellerOrder->pickup_code,
            'shipping_status' => $sellerOrder->shipping_status,
            'ready' => $sellerOrder->shipping_status === 'ready',
        ]);
    }

    public function verify(Request $request, SellerOrder $sellerOrder): JsonResponse
    {
        $store = $this->sellerStore($request);
        abort_unless($sellerOrder->seller_profile_id === $store->id, 404);
        abort_if($sellerOrder->shipping_method !== 'store_pickup', 422, 'Metode pengambilan tidak sesuai.');
        abort_if($sellerOrder->shipping_status !== 'ready', 422, 'Pesanan belum siap diambil.');
        abort_if($sellerOrder->status === 'cancelled', 422, 'Pesanan sudah dibatalkan.');

        $code = strtoupper((string) $request->string('pickup_code')->value());
        abort_if($code === '' || ! hash_equals((string) $sellerOrder->pickup_code, $code), 422, 'Kode pengambilan salah.');

        // `payment_status` tidak dipaksa menjadi `paid`: untuk transfer bank/QRIS
        // uang baru dianggap masuk setelah verifikasi manual.
        $sellerOrder->update(['shipping_status' => 'completed', 'status' => 'completed']);
        $sellerOrder->shipment()->update(['status' => 'completed', 'completed_at' => now()]);

        $codSettled = false;

        foreach ($sellerOrder->payments as $payment) {
            if ($payment->method === Payment::METHOD_COD && $payment->status !== Payment::STATUS_PAID) {
                $payment->update(['status' => Payment::STATUS_PAID, 'paid_at' => now(), 'verified_at' => now()]);
                $codSettled = true;
            }
        }

        if ($codSettled) {
            $sellerOrder->update(['payment_status' => Payment::STATUS_PAID]);
        }

        $sellerOrder->order->refreshStatus();

        AuditLogger::log('ORDER_COMPLETED', 'SellerOrder', $sellerOrder->id, [
            'order_number' => $sellerOrder->order->order_number,
            'shipping_method' => 'store_pickup',
            'source' => 'api',
        ]);
        UserNotification::send(
            $sellerOrder->order->buyer_id,
            'Pesanan selesai',
            "Pesanan {$sellerOrder->order->order_number} selesai. Terima kasih telah berbelanja.",
            'order_completed'
        );

        return response()->json([
            'message' => 'Kode valid, pesanan berhasil diambil.',
            'data' => [
                'id' => $sellerOrder->id,
                'order_number' => $sellerOrder->order->order_number,
                'shipping_status' => $sellerOrder->shipping_status,
                'status' => $sellerOrder->status,
            ],
        ]);
    }

    private function sellerStore(Request $request): SellerProfile
    {
        abort_unless($request->user()->isSeller(), 403);
        $store = $request->user()->sellerProfile()->first();

        return $store ?? abort(403, 'Toko tidak ditemukan.');
    }
}
