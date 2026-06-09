<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditTrailService
{
    public function log(string $entityType, int $entityId, string $action, $oldValues = null, $newValues = null): void
    {
        $userId = Auth::id();
        $now = current_datetime();

        \DB::table('tbl_audit_trails')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values' => $newValues !== null ? json_encode($newValues) : null,
            'created_by' => $userId,
            'created_on' => $now,
            'modified_by' => $userId,
            'modified_on' => $now,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 500),
        ]);
    }
}
