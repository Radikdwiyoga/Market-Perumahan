<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'title', 'body', 'type', 'read_at'])]
/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $body
 * @property string|null $type
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class UserNotification extends Model
{
    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Buat notifikasi untuk satu user.
     */
    public static function send(int $userId, string $title, string $body, ?string $type = null): static
    {
        return static::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
        ]);
    }

    /**
     * Kirim notifikasi ke seluruh admin aktif.
     */
    public static function sendToAdmins(string $title, string $body, ?string $type = null): void
    {
        $adminIds = User::query()->where('role', 'admin')->where('status', 'active')->pluck('id');

        foreach ($adminIds as $adminId) {
            static::send($adminId, $title, $body, $type);
        }
    }

    public function markAsRead(): static
    {
        $this->update(['read_at' => now()]);

        return $this;
    }
}
