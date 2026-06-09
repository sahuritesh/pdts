<?php

namespace App\Services;

use Carbon\Carbon;

class DelayCalculationService
{
    /**
     * delay_days = end_date - start_date (inclusive calendar days per Excel "Total Days Delayed").
     */
    public function calculateDelayDays(?string $startDate, ?string $endDate): int
    {
        if (empty($startDate) || empty($endDate)) {
            return 0;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
            $days = $start->diffInDays($end, false);

            return max(0, (int) $days);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
