<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['order_number', 'buyer_id', 'subtotal', 'shipping_fee', 'total_amount', 'status'])]
/**
 * @property int $id
 * @property string $order_number
 * @property int $buyer_id
 * @property int $subtotal
 * @property int $shipping_fee
 * @property int $total_amount
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $buyer
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, SellerOrder> $sellerOrders
 * @property-read Collection<int, Complaint> $complaints
 */
class Order extends Model
{
    protected function casts(): array
    {
        return ['subtotal' => 'integer', 'shipping_fee' => 'integer', 'total_amount' => 'integer'];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Sinkronkan status order induk berdasarkan status seluruh sub-order.
     *
     * @return $this
     */
    public function refreshStatus(): static
    {
        $this->load('sellerOrders');

        $status = match (true) {
            $this->sellerOrders->every(fn (SellerOrder $item): bool => $item->status === 'completed') => 'completed',
            $this->sellerOrders->every(fn (SellerOrder $item): bool => $item->status === 'cancelled') => 'cancelled',
            $this->sellerOrders->contains(fn (SellerOrder $item): bool => $item->status === 'processing') => 'processing',
            default => 'pending',
        };

        $this->update(['status' => $status]);

        return $this;
    }
}
