<?php

namespace App\Http\Resources;

use App\Models\SellerPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin SellerPaymentSetting
 */
class SellerPaymentSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_name' => $this->bank_account_name,
            'qris_image_url' => $this->qris_image ? Storage::disk('public')->url($this->qris_image) : null,
            'qris_status' => $this->qris_status,
            'qris_uploaded_at' => $this->qris_uploaded_at?->toIso8601String(),
        ];
    }
}
