<?php

namespace App\Support;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * Catat aktivitas penting ke audit log.
     *
     * @param  array<int|string, mixed>  $metadata
     */
    public static function log(string $action, ?string $entityType = null, int|string|null $entityId = null, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
