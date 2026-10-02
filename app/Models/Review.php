<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['buyer_id', 'order_id', 'product_id', 'seller_profile_id', 'rating', 'review'])]
/**
 * @property int $id
 * @property int $buyer_id
 * @property int $order_id
 * @property int $product_id
 * @property int $seller_profile_id
 * @property int $rating
 * @property string|null $review
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $buyer
 * @property-read Product $product
 * @property-read SellerProfile $sellerProfile
 */
class Review extends Model
{
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
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
