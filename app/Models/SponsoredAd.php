<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['title', 'type', 'media_path', 'link_url', 'caption', 'status', 'order'])]
/**
 * @property int $id
 * @property string $title
 * @property string $type
 * @property string $media_path
 * @property string|null $link_url
 * @property string|null $caption
 * @property string $status
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SponsoredAd extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

