<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EscalationService
{
    public function resolveEscalationLevel(string $severity, string $alertLevel = ''): ?int
    {
        $row = DB::table('tbl_ews_escalation_matrix')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->where('trigger_severity', $severity)
            ->first();

        if ($row) {
            return (int) $row->escalation_level;
        }

        if ($alertLevel !== '') {
            $row = DB::table('tbl_ews_escalation_matrix')
                ->where('is_delete', 0)
                ->where('status', 1)
                ->where('trigger_alert_level', $alertLevel)
                ->first();
            if ($row) {
                return (int) $row->escalation_level;
            }
        }

        return null;
    }
}
