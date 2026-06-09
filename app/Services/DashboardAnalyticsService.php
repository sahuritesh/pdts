<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * Aggregate Module 1 metrics for the main dashboard.
     */
    public function getModule1Analytics(): array
    {
        return [
            'kpis' => $this->getKpis(),
            'delays_by_severity' => $this->getDelaysBySeverity(),
            'delays_by_category' => $this->getDelaysByCategory(),
            'project_status' => $this->getProjectStatusBreakdown(),
            'delay_trend' => $this->getDelayTrendByMonth(6),
            'delays_by_hospital' => $this->getDelaysByHospital(8),
            'mitigation_status' => $this->getMitigationStatusBreakdown(),
            'recent_critical_delays' => $this->getRecentCriticalDelays(5),
        ];
    }

    private function getKpis(): array
    {
        $projectQuery = DB::table('tbl_projects')->where('is_delete', 0);
        $delayQuery = DB::table('tbl_delay_registers')->where('is_delete', 0);

        $totalProjects = (clone $projectQuery)->count();
        $activeProjects = (clone $projectQuery)->where('project_status', 'active')->count();
        $delayedProjects = (clone $projectQuery)->where('project_status', 'delayed')->count();
        $completedProjects = (clone $projectQuery)->where('project_status', 'completed')->count();

        $openDelays = (clone $delayQuery)->whereIn('register_status', ['open', 'in_progress'])->count();
        $totalDelays = (clone $delayQuery)->count();
        $avgDelayDays = round((float) ((clone $delayQuery)->avg('delay_days') ?? 0), 1);
        $criticalCount = (clone $delayQuery)->whereIn('severity', ['critical', 'showstopper'])->count();

        $totalDelayCost = (float) DB::table('tbl_delay_financial_impacts as fi')
            ->join('tbl_delay_registers as dr', 'dr.id', '=', 'fi.delay_register_id')
            ->where('fi.is_delete', 0)
            ->where('dr.is_delete', 0)
            ->sum('fi.total_project_delay_cost');

        if ($totalDelayCost <= 0) {
            $totalDelayCost = (float) (clone $projectQuery)->sum('total_delay_cost');
        }

        $openMitigations = (int) DB::table('tbl_delay_mitigations')
            ->where('is_delete', 0)
            ->whereIn('current_status', ['open', 'in_progress', 'escalated'])
            ->count();

        $attachmentCount = (int) DB::table('tbl_delay_attachments')
            ->where('is_delete', 0)
            ->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'delayed_projects' => $delayedProjects,
            'completed_projects' => $completedProjects,
            'open_delays' => $openDelays,
            'total_delays' => $totalDelays,
            'avg_delay_days' => $avgDelayDays,
            'critical_delays' => $criticalCount,
            'total_delay_cost' => round($totalDelayCost, 2),
            'open_mitigations' => $openMitigations,
            'attachment_count' => $attachmentCount,
        ];
    }

    private function getDelaysBySeverity(): array
    {
        $order = ['minor', 'moderate', 'critical', 'showstopper'];
        $labelsMap = [
            'minor' => 'Minor',
            'moderate' => 'Moderate',
            'critical' => 'Critical',
            'showstopper' => 'Showstopper',
        ];
        $colorsMap = [
            'minor' => '#1cbb8c',
            'moderate' => '#fcb92c',
            'critical' => '#ff3d60',
            'showstopper' => '#2d3448',
        ];

        $rows = DB::table('tbl_delay_registers')
            ->select('severity', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $labels = [];
        $series = [];
        $colors = [];
        foreach ($order as $key) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0 || in_array($key, $order, true)) {
                $labels[] = $labelsMap[$key];
                $series[] = $count;
                $colors[] = $colorsMap[$key];
            }
        }

        return compact('labels', 'series', 'colors');
    }

    private function getDelaysByCategory(): array
    {
        $rows = DB::table('tbl_delay_registers as dr')
            ->leftJoin('tbl_delay_categories as dc', 'dc.id', '=', 'dr.delay_category_id')
            ->where('dr.is_delete', 0)
            ->select(DB::raw("COALESCE(dc.category_name, 'Uncategorised') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getProjectStatusBreakdown(): array
    {
        $labelsMap = [
            'active' => 'Active',
            'delayed' => 'Delayed',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ];

        $rows = DB::table('tbl_projects')
            ->select('project_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('project_status')
            ->pluck('total', 'project_status');

        $labels = [];
        $series = [];
        foreach ($labelsMap as $key => $label) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $label;
                $series[] = $count;
            }
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => ['#1cbb8c', '#fcb92c', '#4aa3ff', '#adb5bd'],
        ];
    }

    private function getDelayTrendByMonth(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = DB::table('tbl_delay_registers')
            ->select(DB::raw("DATE_FORMAT(delay_start_date, '%Y-%m') as month_key"), DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->whereNotNull('delay_start_date')
            ->where('delay_start_date', '>=', $start->toDateString())
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('total', 'month_key');

        $labels = [];
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $series[] = (int) ($rows[$key] ?? 0);
        }

        return compact('labels', 'series');
    }

    private function getDelaysByHospital(int $limit = 8): array
    {
        $rows = DB::table('tbl_delay_registers as dr')
            ->join('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->where('dr.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->whereNotNull('tp.hospital_name')
            ->where('tp.hospital_name', '!=', '')
            ->select('tp.hospital_name as label', DB::raw('COUNT(*) as total'))
            ->groupBy('tp.hospital_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getMitigationStatusBreakdown(): array
    {
        $labelsMap = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'escalated' => 'Escalated',
            'closed' => 'Closed',
        ];

        $rows = DB::table('tbl_delay_mitigations')
            ->select('current_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('current_status')
            ->pluck('total', 'current_status');

        $labels = [];
        $series = [];
        foreach ($labelsMap as $key => $label) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $label;
                $series[] = $count;
            }
        }

        return [
            'labels' => $labels,
            'series' => $series,
            'colors' => ['#fcb92c', '#4aa3ff', '#ff3d60', '#1cbb8c'],
        ];
    }

    private function getRecentCriticalDelays(int $limit = 5): array
    {
        return DB::table('tbl_delay_registers as dr')
            ->leftJoin('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->leftJoin('tbl_delay_categories as dc', 'dc.id', '=', 'dr.delay_category_id')
            ->where('dr.is_delete', 0)
            ->whereIn('dr.severity', ['critical', 'showstopper'])
            ->orderByDesc('dr.delay_days')
            ->orderByDesc('dr.id')
            ->limit($limit)
            ->get([
                'dr.delay_title',
                'dr.severity',
                'dr.delay_days',
                'dr.register_status',
                'tp.project_code',
                'tp.hospital_name',
                'dc.category_name',
            ])
            ->map(fn ($row) => [
                'title' => $row->delay_title,
                'severity' => ucfirst($row->severity ?? ''),
                'days' => (int) ($row->delay_days ?? 0),
                'status' => ucfirst(str_replace('_', ' ', $row->register_status ?? '')),
                'project' => trim(($row->project_code ?? '') . ($row->hospital_name ? ' — ' . $row->hospital_name : '')),
                'category' => $row->category_name ?? '—',
            ])
            ->all();
    }
}
