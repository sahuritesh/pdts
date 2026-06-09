<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DelaySeverityService
{
    /**
     * Resolve severity from delay days and licensing/opening flag.
     */
    public function resolveSeverity(int $delayDays, bool $licensingOpeningsAffected = false): string
    {
        if ($licensingOpeningsAffected) {
            return 'showstopper';
        }

        $rules = DB::table('tbl_delay_severity_rules')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->where('requires_licensing_flag', 0)
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->severity_code === 'showstopper') {
                continue;
            }
            $min = (int) ($rule->min_delay_days ?? 0);
            $max = $rule->max_delay_days !== null ? (int) $rule->max_delay_days : null;

            if ($delayDays >= $min && ($max === null || $delayDays <= $max)) {
                return $rule->severity_code;
            }
        }

        if ($delayDays > 30) {
            return 'critical';
        }

        return 'minor';
    }

    public function severityToAlertLevel(string $severity): string
    {
        $map = [
            'minor' => 'green',
            'moderate' => 'amber',
            'critical' => 'red',
            'showstopper' => 'black',
        ];

        return $map[$severity] ?? 'green';
    }
}
