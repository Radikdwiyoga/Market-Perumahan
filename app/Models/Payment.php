<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['order_id', 'seller_order_id', 'buyer_id', 'seller_profile_id', 'method', 'amount', 'status', 'proof_image', 'qris_image_snapshot', 'rejection_reason', 'paid_at', 'verified_at'])]
/**
 * Ledger pembayaran satu sub-order.
 *
 * `method`:
 * - `bank_transfer`: pembeli transfer manual, lalu mengunggah bukti.
 * - `qris`         : pembeli memindai QRIS toko, lalu mengunggah bukti.
 * - `cod`          : dibayar saat barang diterima, dikonfirmasi penjual.
 *
 * @property int $id
 * @property int $order_id
 * @property int $seller_order_id
 * @property int $buyer_id
 * @property int $seller_profile_id
 * @property string $method
 * @property int $amount
 * @property string $status
 * @property string|null $proof_image
 * @property string|null $qris_image_snapshot
 * @property string|null $rejection_reason
 * @property Carbon|null $paid_at
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SellerOrder $sellerOrder
 */
class Payment extends Model
{
    /**
     * Metode transfer bank manual.
     */
    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    /**
     * Metode QRIS toko.
     */
    public const METHOD_QRIS = 'qris';

    /**
     * Metode COD: dana diterima saat barang diterima.
     */
    public const METHOD_COD = 'cod';

    /**
     * Menunggu verifikasi manual penjual/admin.
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Sudah lunas: keputusan final, tidak bisa diubah.
     */
    public const STATUS_PAID = 'paid';

    /**
     * Ditolak atau kedaluwarsa: butuh pembayaran baru dari pembeli.
     */
    public const STATUS_FAILED = 'failed';

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    /**
     * Pembayaran masih menunggu keputusan manual penjual/admin.
     *
     * Hanya status `pending` yang boleh diverifikasi atau ditolak. `paid` sudah
     * lunas dan `failed` adalah keputusan final (ditolak admin atau kedaluwarsa),
     * sehingga tidak boleh diaktifkan kembali oleh penjual. Setelah ditolak,
     * pembeli harus membuat pembayaran baru lewat endpoint pembayaran order.
     */
    public function isVerifiable(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Apakah pembeli perlu mengunggah bukti pembayaran.
     */
    public function requiresProof(): bool
    {
        return in_array($this->method, [self::METHOD_BANK_TRANSFER, self::METHOD_QRIS], true);
    }

    /**
     * @return array<string, string>
     */
    public static function methodLabels(): array
    {
        return [
            self::METHOD_BANK_TRANSFER => 'Transfer bank',
            self::METHOD_QRIS => 'QRIS',
            self::METHOD_COD => 'COD',
        ];
    }

    public function methodLabel(): string
    {
        return self::methodLabels()[$this->method] ?? ucfirst(str_replace('_', ' ', $this->method));
    }
}
