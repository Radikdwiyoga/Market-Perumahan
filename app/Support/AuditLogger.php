<?php

namespace App\Support;

use App\Models\AuditLog;

class AuditLogger
{
    /**
     * Catat aktivitas penting ke audit log.
     *
     * Aktivitas otomatis (command/Antrian) tidak punya pengguna atau IP, jadi
     * pemanggil boleh menyertakan `actor` agar jejaknya tetap bisa ditelusuri.
     *
     * @param  array<int|string, mixed>  $metadata
     */
    public static function log(string $action, ?string $entityType = null, int|string|null $entityId = null, array $metadata = [], ?string $actor = null): void
    {
        AuditLog::query()->create([
            'user_id' => $actor !== null && ctype_digit($actor) ? (int) $actor : auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
