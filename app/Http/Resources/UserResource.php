<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'block' => $this->block,
            'house_number' => $this->house_number,
            'role' => $this->role,
            'status' => $this->status,
            'store' => $this->when(
                $this->isSeller() && $this->sellerProfile !== null,
                fn () => [
                    'id' => $this->sellerProfile->id,
                    'store_name' => $this->sellerProfile->store_name,
                    'status' => $this->sellerProfile->status,
                    'verification_status' => $this->sellerProfile->verification_status,
                    'rejection_reason' => $this->sellerProfile->rejection_reason,
                ],
            ),
        ];
    }
}
