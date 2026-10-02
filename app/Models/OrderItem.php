<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['order_id', 'seller_profile_id', 'product_id', 'product_name', 'price', 'quantity', 'note', 'subtotal'])]
/**
 * @property int $id
 * @property int $order_id
 * @property int $seller_profile_id
 * @property int $product_id
 * @property string $product_name
 * @property int $price
 * @property int $quantity
 * @property string|null $note
 * @property int $subtotal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Product $product
 * @property-read SellerProfile $sellerProfile
 */
class OrderItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }
}
