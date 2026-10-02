<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable(['buyer_id', 'seller_profile_id', 'last_message_at'])]
/**
 * @property int $id
 * @property int $buyer_id
 * @property int $seller_profile_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $buyer
 * @property-read SellerProfile $sellerProfile
 * @property-read Collection<int, ChatMessage> $messages
 * @property-read ChatMessage|null $latestMessage
 */
class ChatConversation extends Model
{
    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function sellerProfile(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    public function isParticipant(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->sellerProfile?->user_id === $user->id;
    }

    public function counterpart(User $user): User
    {
        return $this->buyer_id === $user->id ? $this->sellerProfile->user : $this->buyer;
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markReadBy(User $user): void
    {
        $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public static function unreadConversationCountFor(User $user): int
    {
        if ($user->isAdmin()) {
            return 0;
        }

        $query = static::query()->whereHas('messages', function ($query) use ($user): void {
            $query->where('sender_id', '!=', $user->id)->whereNull('read_at');
        });

        if ($user->isSeller()) {
            return $query->where('seller_profile_id', $user->sellerProfile?->id)->count();
        }

        return $query->where('buyer_id', $user->id)->count();
    }
}
