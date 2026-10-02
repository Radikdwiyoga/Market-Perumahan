<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use App\Models\SellerOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SellerOrder
 */
class SellerOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order?->order_number,
            'store' => $this->sellerProfile ? [
                'id' => $this->sellerProfile->id,
                'store_name' => $this->sellerProfile->store_name,
            ] : null,
            'buyer' => $this->whenLoaded('order', fn (): ?array => $this->order->buyer ? [
                'id' => $this->order->buyer->id,
                'name' => $this->order->buyer->name,
                'phone' => $this->order->buyer->phone,
            ] : null),
            'subtotal' => $this->subtotal,
            'shipping_fee' => $this->shipping_fee,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'shipping_method' => $this->shipping_method,
            'shipping_status' => $this->shipping_status,
            'pickup_code' => $this->pickup_code,
            'payment_due_at' => $this->payment_due_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('order', fn (): mixed => $this->order->items
                ->where('seller_profile_id', $this->seller_profile_id)
                ->values()
                ->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'note' => $item->note,
                    'subtotal' => $item->subtotal,
                ])),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'shipment' => $this->whenLoaded('shipment', function (): ?array {
                $shipment = $this->shipment->first();

                return $shipment ? [
                    'method' => $shipment->method,
                    'address' => $shipment->address,
                    'recipient_name' => $shipment->recipient_name,
                    'recipient_phone' => $shipment->recipient_phone,
                    'shipping_fee' => $shipment->shipping_fee,
                    'status' => $shipment->status,
                    'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                    'completed_at' => $shipment->completed_at?->toIso8601String(),
                ] : null;
            }),
        ];
    }
}
