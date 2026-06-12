<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialImpactService
{
    /**
     * Calculate direct, opportunity, and total delay cost from line items.
     */
    public function applyCalculations(array $data): array
    {
        $direct = round(
            $this->toAmount($data['labor_overrun'] ?? 0)
            + $this->toAmount($data['material_cost_overrun'] ?? 0)
            + $this->toAmount($data['contractor_claims'] ?? 0)
            + $this->toAmount($data['equipment_storage_charges'] ?? 0),
            2
        );

        $opportunity = round(
            $this->toAmount($data['delayed_admissions'] ?? 0)
            + $this->toAmount($data['delayed_surgeries'] ?? 0)
            + $this->toAmount($data['delayed_revenue'] ?? 0)
            + $this->toAmount($data['lost_operational_days'] ?? 0),
            2
        );

        $data['labor_overrun'] = $this->toAmount($data['labor_overrun'] ?? 0);
        $data['material_cost_overrun'] = $this->toAmount($data['material_cost_overrun'] ?? 0);
        $data['contractor_claims'] = $this->toAmount($data['contractor_claims'] ?? 0);
        $data['equipment_storage_charges'] = $this->toAmount($data['equipment_storage_charges'] ?? 0);
        $data['delayed_admissions'] = $this->toAmount($data['delayed_admissions'] ?? 0);
        $data['delayed_surgeries'] = $this->toAmount($data['delayed_surgeries'] ?? 0);
        $data['delayed_revenue'] = $this->toAmount($data['delayed_revenue'] ?? 0);
        $data['lost_operational_days'] = $this->toAmount($data['lost_operational_days'] ?? 0);
        $data['direct_cost_total'] = $direct;
        $data['opportunity_cost_total'] = $opportunity;
        $data['total_project_delay_cost'] = round($direct + $opportunity, 2);

        return $data;
    }

    /**
     * Roll up all delay financial impacts into tbl_projects.total_delay_cost.
     */
    public function syncProjectDelayCost(int $projectId): void
    {
        if ($projectId <= 0) {
            return;
        }

        $total = DB::table('tbl_delay_financial_impacts as fi')
            ->where('fi.is_delete', 0)
            ->where('fi.project_id', $projectId)
            ->sum('fi.total_project_delay_cost');

        DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->update([
                'total_delay_cost' => round((float) $total, 2),
                'updated_on' => current_datetime(),
                'updated_by' => Auth::id(),
            ]);
    }

    private function toAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) str_replace(',', '', (string) $value), 2);
    }
}
