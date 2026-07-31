<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogService
{
    public function log(
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $note = null,
        ?Authenticatable $user = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'note' => $note,
        ]);
    }
}
