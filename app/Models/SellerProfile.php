<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'store_name', 'description', 'phone', 'address', 'image', 'open_time', 'close_time', 'enable_delivery', 'enable_pickup', 'delivery_fee', 'min_order_amount', 'free_shipping_threshold', 'status', 'verification_status', 'rejection_reason', 'verified_by', 'verified_at', 'submitted_at'])]
/**
 * @property int $id
 * @property int $user_id
 * @property string $store_name
 * @property string|null $description
 * @property string $phone
 * @property string $address
 * @property string|null $image
 * @property Carbon|null $open_time
 * @property Carbon|null $close_time
 * @property bool $enable_delivery
 * @property bool $enable_pickup
 * @property int $delivery_fee
 * @property int $min_order_amount
 * @property int|null $free_shipping_threshold
 * @property string $status
 * @property string $verification_status
 * @property string|null $rejection_reason
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $verifier
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Review> $reviews
 * @property-read Collection<int, ChatConversation> $conversations
 * @property-read SellerPaymentSetting|null $paymentSetting
 */
class SellerProfile extends Model
{
    protected function casts(): array
    {
        return [
            'open_time' => 'datetime:H:i',
            'close_time' => 'datetime:H:i',
            'enable_delivery' => 'boolean',
            'enable_pickup' => 'boolean',
            'delivery_fee' => 'integer',
            'min_order_amount' => 'integer',
            'free_shipping_threshold' => 'integer',
            'verified_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function paymentSetting(): HasOne
    {
        return $this->hasOne(SellerPaymentSetting::class);
    }

    public function isOpenNow(): bool
    {
        if ($this->status !== 'open' || ! $this->open_time || ! $this->close_time) {
            return $this->status === 'open';
        }

        $now = now()->format('H:i');

        return $this->open_time->format('H:i') <= $now && $now <= $this->close_time->format('H:i');
    }

    public function businessHoursLabel(): string
    {
        if (! $this->open_time || ! $this->close_time) {
            return 'Jam operasional tidak ditentukan';
        }

        return $this->open_time->format('H:i').' - '.$this->close_time->format('H:i');
    }

    /**
     * Metode pengiriman yang diaktifkan toko untuk pembeli.
     *
     * @return list<string>
     */
    public function availableShippingMethods(): array
    {
        return array_values(array_filter([
            $this->enable_delivery ? 'seller_delivery' : null,
            $this->enable_pickup ? 'store_pickup' : null,
        ]));
    }

    public function isShippingMethodAvailable(string $method): bool
    {
        return in_array($method, $this->availableShippingMethods(), true);
    }

    public function meetsMinimumOrder(int $subtotal): bool
    {
        return $subtotal >= $this->min_order_amount;
    }

    /**
     * Ongkos kirim untuk satu sub-order, memperhitungkan gratis ongkir.
     */
    public function shippingFeeFor(string $method, int $subtotal): int
    {
        if ($method !== 'seller_delivery' || ! $this->meetsMinimumOrder($subtotal)) {
            return 0;
        }

        if ($this->free_shipping_threshold && $subtotal >= $this->free_shipping_threshold) {
            return 0;
        }

        return $this->delivery_fee;
    }

    public function isVerificationApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isVerificationPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function isVerificationRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    public function canSubmitVerification(): bool
    {
        return ! $this->isVerificationPending() && ! $this->isVerificationApproved();
    }

    public function verificationStatusLabel(): string
    {
        return match ($this->verification_status) {
            'pending' => 'Menunggu verifikasi',
            'approved' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Belum diajukan',
        };
    }
}
