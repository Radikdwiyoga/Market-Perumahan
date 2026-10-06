<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['order_id', 'seller_profile_id', 'subtotal', 'shipping_fee', 'total_amount', 'payment_status', 'shipping_method', 'shipping_status', 'status', 'pickup_code', 'payment_due_at'])]
/**
 * @property int $id
 * @property int $order_id
 * @property int $seller_profile_id
 * @property int $subtotal
 * @property int $shipping_fee
 * @property int $total_amount
 * @property string $payment_status
 * @property string $shipping_method
 * @property string $shipping_status
 * @property string $status
 * @property string|null $pickup_code
 * @property Carbon|null $payment_due_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read SellerProfile $sellerProfile
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Shipment> $shipment
 */
class SellerOrder extends Model
{
    protected function casts(): array
    {
        return ['payment_due_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canBeFulfilled(): bool
    {
        $payment = $this->payments()->latest('id')->first();

        return $payment !== null
            && ($payment->status === Payment::STATUS_PAID
                || ($payment->method === Payment::METHOD_COD && $payment->status === Payment::STATUS_PENDING));
    }

    public function settleCodPayment(): void
    {
        $payment = $this->payments()->latest('id')->first();

        if ($payment?->method !== Payment::METHOD_COD || $payment->status !== Payment::STATUS_PENDING) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);
        $this->update(['payment_status' => Payment::STATUS_PAID]);
    }

    public function shipment(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
