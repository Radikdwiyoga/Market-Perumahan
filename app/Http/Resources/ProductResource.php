<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount_percent' => $this->discount_percent,
            'effective_price' => $this->effectivePrice(),
            'stock' => $this->stock,
            'status' => $this->status,
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'store' => $this->whenLoaded('sellerProfile', fn () => [
                'id' => $this->sellerProfile->id,
                'store_name' => $this->sellerProfile->store_name,
                'address' => $this->sellerProfile->address,
                'phone' => $this->sellerProfile->phone,
                'status' => $this->sellerProfile->status,
                'image_url' => $this->sellerProfile->image ? asset('storage/'.$this->sellerProfile->image) : null,
                'open_time' => $this->sellerProfile->open_time?->format('H:i'),
                'close_time' => $this->sellerProfile->close_time?->format('H:i'),
                'is_open_now' => $this->sellerProfile->isOpenNow(),
                'enable_delivery' => $this->sellerProfile->enable_delivery,
                'enable_pickup' => $this->sellerProfile->enable_pickup,
                'delivery_fee' => $this->sellerProfile->delivery_fee,
                'min_order_amount' => $this->sellerProfile->min_order_amount,
                'free_shipping_threshold' => $this->sellerProfile->free_shipping_threshold,
            ]),
            'rating' => [
                'average' => $this->reviews_avg !== null ? round((float) $this->reviews_avg, 2) : null,
                'count' => (int) ($this->reviews_count ?? 0),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
