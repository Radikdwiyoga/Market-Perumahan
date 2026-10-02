<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\UserNotification;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired';

    protected $description = 'Batalkan sub-order yang melewati batas waktu pembayaran dan kembalikan stok produk';

    public function handle(): int
    {
        $expired = SellerOrder::query()
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<=', now())
            ->with(['order.items', 'payments'])
            ->get();

        foreach ($expired as $sellerOrder) {
            $order = $sellerOrder->order;

            foreach ($order->items as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            $sellerOrder->payments()->update(['status' => 'failed']);
            $sellerOrder->update(['payment_status' => 'failed', 'status' => 'cancelled']);
            $order->refreshStatus();

            $orderNumber = $order->order_number;
            AuditLogger::log('ORDER_CANCELLED', 'SellerOrder', $sellerOrder->id, ['order_number' => $orderNumber, 'reason' => 'payment_expired']);
            UserNotification::send($order->buyer_id, 'Pesanan dibatalkan', "Pesanan {$orderNumber} dibatalkan karena pembayaran melebihi batas waktu.", 'order_cancelled');
            UserNotification::send($sellerOrder->sellerProfile->user_id, 'Pesanan dibatalkan', "Pesanan {$orderNumber} dibatalkan karena pembayaran melewati batas waktu.", 'order_cancelled');
        }

        $this->info("{$expired->count()} sub-order kedaluwarsa dibatalkan.");

        return self::SUCCESS;
    }
}
