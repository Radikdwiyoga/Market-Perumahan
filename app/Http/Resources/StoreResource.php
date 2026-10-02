<?php

namespace App\Http\Resources;

use App\Models\SellerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SellerProfile
 */
class StoreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'description' => $this->description,
            'phone' => $this->phone,
            'address' => $this->address,
            'status' => $this->status,
            'image_url' => $this->image ? asset('storage/'.$this->image) : null,
            'open_time' => $this->open_time?->format('H:i'),
            'close_time' => $this->close_time?->format('H:i'),
            'business_hours' => $this->businessHoursLabel(),
            'is_open_now' => $this->isOpenNow(),
            'shipping' => [
                'enable_delivery' => $this->enable_delivery,
                'enable_pickup' => $this->enable_pickup,
                'delivery_fee' => $this->delivery_fee,
                'min_order_amount' => $this->min_order_amount,
                'free_shipping_threshold' => $this->free_shipping_threshold,
                'available_methods' => $this->availableShippingMethods(),
            ],
            'rating' => [
                'average' => $this->reviews_avg !== null ? round((float) $this->reviews_avg, 2) : null,
                'count' => (int) ($this->reviews_count ?? 0),
            ],
            'products_count' => (int) ($this->products_count ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
