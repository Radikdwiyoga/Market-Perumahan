<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['seller_profile_id', 'bank_name', 'bank_account_number', 'bank_account_name', 'qris_image', 'qris_status', 'qris_uploaded_at'])]
/**
 * Rekening bank dan gambar QRIS milik satu toko.
 *
 * @property int $id
 * @property int $seller_profile_id
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property string|null $qris_image
 * @property string $qris_status
 * @property Carbon|null $qris_uploaded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SellerProfile $sellerProfile
 */
class SellerPaymentSetting extends Model
{
    protected function casts(): array
    {
        return ['qris_uploaded_at' => 'datetime'];
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }
}
