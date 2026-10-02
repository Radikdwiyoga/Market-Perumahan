<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SellerOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn (): mixed => $this->items->map(fn (OrderItem $item): array => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'note' => $item->note,
                'subtotal' => $item->subtotal,
            ])),
            'seller_orders' => $this->whenLoaded('sellerOrders', fn (): mixed => $this->sellerOrders->map(fn (SellerOrder $item): array => [
                'id' => $item->id,
                'store' => $item->sellerProfile?->store_name,
                'subtotal' => $item->subtotal,
                'shipping_fee' => $item->shipping_fee,
                'total_amount' => $item->total_amount,
                'status' => $item->status,
                'payment_status' => $item->payment_status,
                'shipping_method' => $item->shipping_method,
                'shipping_status' => $item->shipping_status,
                'pickup_code' => $item->pickup_code,
                'payment_due_at' => $item->payment_due_at?->toIso8601String(),
            ])),
        ];
    }
}
