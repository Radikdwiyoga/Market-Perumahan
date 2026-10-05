<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->whenLoaded('sellerOrder', fn (): ?string => $this->sellerOrder->order?->order_number),
            'seller_order_id' => $this->seller_order_id,
            'store' => $this->whenLoaded('sellerOrder', fn (): ?array => $this->sellerOrder->sellerProfile ? [
                'id' => $this->sellerOrder->sellerProfile->id,
                'store_name' => $this->sellerOrder->sellerProfile->store_name,
            ] : null),
            'method' => $this->method,
            'method_label' => $this->methodLabel(),
            'amount' => $this->amount,
            'status' => $this->status,
            // `true` hanya saat masih `pending`: pembayaran yang sudah lunas atau
            // sudah ditolak tidak lagi menunggu keputusan manual.
            'requires_manual_verification' => $this->isVerifiable(),
            'requires_proof' => $this->requiresProof(),
            'proof_image_url' => $this->proof_image ? Storage::disk('public')->url($this->proof_image) : null,
            'qris_image_snapshot_url' => $this->qris_image_snapshot ? Storage::disk('public')->url($this->qris_image_snapshot) : null,
            'rejection_reason' => $this->rejection_reason,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
