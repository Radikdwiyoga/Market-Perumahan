<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['seller_profile_id', 'category_id', 'name', 'description', 'price', 'discount_percent', 'stock', 'image', 'status'])]
/**
 * @property int $id
 * @property int $seller_profile_id
 * @property int $category_id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property int|null $discount_percent
 * @property int $stock
 * @property string|null $image
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SellerProfile $sellerProfile
 * @property-read Category $category
 * @property-read Collection<int, Review> $reviews
 */
class Product extends Model
{
    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function hasDiscount(): bool
    {
        return is_numeric($this->discount_percent) && $this->discount_percent > 0;
    }

    public function effectivePrice(): int
    {
        if (! $this->hasDiscount()) {
            return $this->price;
        }

        return (int) round($this->price * (100 - $this->discount_percent) / 100);
    }
}
