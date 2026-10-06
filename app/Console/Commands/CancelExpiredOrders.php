<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired';

    protected $description = 'Batalkan sub-order yang melewati batas waktu pembayaran dan kembalikan stok produk';

    public function handle(): int
    {
        $expired = SellerOrder::query()
            ->where('payment_status', 'pending')
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', now())
            ->with(['order.items', 'payments'])
            ->get();

        foreach ($expired as $sellerOrder) {
            $cancelled = DB::transaction(function () use ($sellerOrder): ?Order {
                $sellerOrder->refresh();

                // Sudah dibayar atau dibatalkan oleh proses lain (mis. verifikasi
                // pembayaran) di antara SELECT dan UPDATE: jangan sentuh lagi.
                if (! in_array($sellerOrder->status, ['pending', 'processing'], true) || $sellerOrder->payment_status !== 'pending') {
                    return null;
                }

                $order = $sellerOrder->order;

                // Hanya barang milik toko yang sub-order ini kedaluwarsa.
                // Memulihkan seluruh `$order->items` akan mengembalikan stok
                // toko lain pada order multi-seller.
                foreach ($order->items->where('seller_profile_id', $sellerOrder->seller_profile_id) as $item) {
                    Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
                }

                $sellerOrder->payments()->update(['status' => 'failed']);
                $sellerOrder->update(['payment_status' => 'failed', 'status' => 'cancelled']);
                $order->refreshStatus();

                return $order;
            });

            if ($cancelled === null) {
                continue;
            }

            $orderNumber = $cancelled->order_number;
            AuditLogger::log('ORDER_CANCELLED', 'SellerOrder', $sellerOrder->id, ['order_number' => $orderNumber, 'reason' => 'payment_expired'], 'system:orders:cancel-expired');
            UserNotification::send($cancelled->buyer_id, 'Pesanan dibatalkan', "Pesanan {$orderNumber} dibatalkan karena pembayaran melebihi batas waktu.", 'order_cancelled');
            UserNotification::send($sellerOrder->sellerProfile->user_id, 'Pesanan dibatalkan', "Pesanan {$orderNumber} dibatalkan karena pembayaran melewati batas waktu.", 'order_cancelled');
        }

        $this->info("{$expired->count()} sub-order kedaluwarsa diproses.");

        return self::SUCCESS;
    }
}
