<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['seller_order_id', 'method', 'address', 'recipient_name', 'recipient_phone', 'shipping_fee', 'status', 'delivered_at', 'completed_at'])]
/**
 * @property int $id
 * @property int $seller_order_id
 * @property string $method
 * @property string $address
 * @property string $recipient_name
 * @property string $recipient_phone
 * @property int $shipping_fee
 * @property string $status
 * @property Carbon|null $delivered_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SellerOrder $sellerOrder
 */
class Shipment extends Model
{
    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }
}
