<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsService
{
    /**
     * Load only analytics datasets for widgets the role may see.
     *
     * @param array<string, bool> $visibleWidgets
     */
    public function getDashboardAnalytics(array $visibleWidgets): array
    {
        $data = [];

        if (!empty($visibleWidgets['m1_kpis'])) {
            $data['kpis'] = $this->getKpis();
        }
        if (!empty($visibleWidgets['m1_chart_severity'])) {
            $data['delays_by_severity'] = $this->getDelaysBySeverity();
        }
        if (!empty($visibleWidgets['m1_chart_category'])) {
            $data['delays_by_category'] = $this->getDelaysByCategory();
        }
        if (!empty($visibleWidgets['m1_chart_project_status'])) {
            $data['project_status'] = $this->getProjectStatusBreakdown();
        }
        if (!empty($visibleWidgets['m1_chart_mitigation'])) {
            $data['department_status'] = $this->getDepartmentStatusBreakdown();
        }
        if (!empty($visibleWidgets['m1_chart_financial'])) {
            $data['financial_impact'] = $this->getFinancialImpactBreakdown();
        }
        if (!empty($visibleWidgets['m1_chart_trend'])) {
            $data['delay_trend'] = $this->getDelayTrendByMonth(6);
        }
        if (!empty($visibleWidgets['m1_chart_hospital'])) {
            $data['delays_by_hospital'] = $this->getDelaysByHospital(8);
        }
        if (!empty($visibleWidgets['m1_table_critical'])) {
            $data['recent_delayed_departments'] = $this->getRecentDelayedDepartments(8);
        }

        return $data;
    }

    /**
     * @param array<string, bool> $visibleWidgets
     */
    private function getModule3AnalyticsForWidgets(array $visibleWidgets): array
    {
        $data = [];

        if (!empty($visibleWidgets['m3_kpis'])) {
            $data['kpis'] = $this->getRenovationKpis();
        }
        if (!empty($visibleWidgets['m3_chart_project_status'])) {
            $data['project_status'] = $this->getRenovationProjectStatusBreakdown();
        }
        if (!empty($visibleWidgets['m3_chart_type'])) {
            $data['renovation_type'] = $this->getRenovationTypeBreakdown();
        }
        if (!empty($visibleWidgets['m3_chart_escalation'])) {
            $data['escalation_status'] = $this->getRenovationEscalationBreakdown();
        }
        if (!empty($visibleWidgets['m3_chart_task_status'])) {
            $data['task_status'] = $this->getRenovationTaskStatusBreakdown();
        }
        if (!empty($visibleWidgets['m3_chart_task_risk'])) {
            $data['task_risk'] = $this->getRenovationTaskRiskBreakdown();
        }
        if (!empty($visibleWidgets['m3_chart_tasks_category'])) {
            $data['tasks_by_category'] = $this->getRenovationTasksByCategory(8);
        }
        if (!empty($visibleWidgets['m3_chart_delay_trend'])) {
            $data['daily_delay_trend'] = $this->getRenovationDailyDelayTrend(6);
        }
        if (!empty($visibleWidgets['m3_table_escalated'])) {
            $data['recent_escalated_projects'] = $this->getRecentEscalatedRenovationProjects(5);
        }

        return $data;
    }

    /**
     * @deprecated Use getDashboardAnalytics($visibleWidgets) instead.
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
            'financial_impact' => $this->getFinancialImpactBreakdown(),
            'recent_critical_delays' => $this->getRecentCriticalDelays(5),
        ];
    }

    /**
     * Aggregate Module 3 renovation metrics for the main dashboard.
     */
    public function getModule3Analytics(): array
    {
        return [
            'kpis' => $this->getRenovationKpis(),
            'project_status' => $this->getRenovationProjectStatusBreakdown(),
            'renovation_type' => $this->getRenovationTypeBreakdown(),
            'escalation_status' => $this->getRenovationEscalationBreakdown(),
            'task_status' => $this->getRenovationTaskStatusBreakdown(),
            'task_risk' => $this->getRenovationTaskRiskBreakdown(),
            'tasks_by_category' => $this->getRenovationTasksByCategory(8),
            'daily_delay_trend' => $this->getRenovationDailyDelayTrend(6),
            'recent_escalated_projects' => $this->getRecentEscalatedRenovationProjects(5),
        ];
    }

    private function getKpis(): array
    {
        $projectQuery = DB::table('tbl_projects')->where('is_delete', 0);
        $delayQuery = DB::table('tbl_delay_registers')->where('is_delete', 0);
        $pdQuery = DB::table('tbl_project_departments')->where('is_delete', 0);

        $totalProjects = (clone $projectQuery)->count();
        $activeProjects = (clone $projectQuery)->where('project_status', 'active')->count();
        $delayedProjects = (clone $projectQuery)->where('project_status', 'delayed')->count();
        $completedProjects = (clone $projectQuery)->where('project_status', 'completed')->count();

        $totalDepartments = (clone $pdQuery)->count();
        $departmentsDelayed = (clone $pdQuery)->where('department_status', 'delay')->count();
        $departmentsInProgress = (clone $pdQuery)->whereIn('department_status', ['start', 'in_progress'])->count();
        $departmentsCompleted = (clone $pdQuery)->where('department_status', 'completed')->count();

        $openDelays = (clone $delayQuery)->whereIn('register_status', ['open', 'in_progress'])->count();
        $totalDelays = (clone $delayQuery)->count();
        $avgDelayDays = round((float) ((clone $pdQuery)->avg('delay_days') ?? 0), 1);
        $criticalCount = (clone $delayQuery)->whereIn('severity', ['critical', 'showstopper'])->count();

        $totalDelayCost = (float) DB::table('tbl_delay_financial_impacts')
            ->where('is_delete', 0)
            ->sum('total_project_delay_cost');

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
            'total_departments' => $totalDepartments,
            'departments_delayed' => $departmentsDelayed,
            'departments_in_progress' => $departmentsInProgress,
            'departments_completed' => $departmentsCompleted,
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
        $deptTable = $this->departmentsTable();
        $nameCol = $this->departmentNameColumn();

        if (Schema::hasTable('tbl_project_departments')) {
            $rows = DB::table('tbl_project_departments as pd')
                ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
                ->where('pd.is_delete', 0)
                ->where('pd.department_status', 'delay')
                ->select(DB::raw("d.$nameCol as label"), DB::raw('COUNT(*) as total'))
                ->groupBy('label')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            if ($rows->isNotEmpty()) {
                return [
                    'labels' => $rows->pluck('label')->all(),
                    'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
                ];
            }
        }

        $rows = DB::table('tbl_delay_registers as dr')
            ->leftJoin("$deptTable as d", 'd.id', '=', 'dr.delay_category_id')
            ->where('dr.is_delete', 0)
            ->select(DB::raw("COALESCE(d.$nameCol, 'Uncategorised') as label"), DB::raw('COUNT(*) as total'))
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getDepartmentStatusBreakdown(): array
    {
        if (!Schema::hasTable('tbl_project_departments')) {
            return ['labels' => [], 'series' => [], 'colors' => []];
        }

        $labelsMap = [
            'pending' => 'Pending',
            'start' => 'Ready',
            'in_progress' => 'In Progress',
            'delay' => 'Delayed',
            'completed' => 'Completed',
        ];
        $colorsMap = [
            'pending' => '#adb5bd',
            'start' => '#4aa3ff',
            'in_progress' => '#003e6b',
            'delay' => '#fcb92c',
            'completed' => '#1cbb8c',
        ];

        $rows = DB::table('tbl_project_departments')
            ->select('department_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('department_status')
            ->pluck('total', 'department_status');

        $labels = [];
        $series = [];
        $colors = [];
        foreach ($labelsMap as $key => $label) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $label;
                $series[] = $count;
                $colors[] = $colorsMap[$key];
            }
        }

        return compact('labels', 'series', 'colors');
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

    private function getFinancialImpactBreakdown(): array
    {
        $row = DB::table('tbl_delay_financial_impacts as fi')
            ->where('fi.is_delete', 0)
            ->selectRaw('COALESCE(SUM(fi.direct_cost_total), 0) as direct_total, COALESCE(SUM(fi.opportunity_cost_total), 0) as opportunity_total')
            ->first();

        $direct = round((float) ($row->direct_total ?? 0), 2);
        $opportunity = round((float) ($row->opportunity_total ?? 0), 2);

        return [
            'labels' => ['Direct cost', 'Opportunity cost'],
            'series' => [$direct, $opportunity],
            'colors' => ['#003e6b', '#fcb92c'],
            'totals' => [
                'direct' => $direct,
                'opportunity' => $opportunity,
                'combined' => round($direct + $opportunity, 2),
            ],
        ];
    }

    private function getRenovationKpis(): array
    {
        $projectQuery = DB::table('tbl_renovation_projects')->where('is_delete', 0);
        $taskQuery = DB::table('tbl_renovation_tasks')->where('is_delete', 0);

        $totalProjects = (clone $projectQuery)->count();
        $inProgressProjects = (clone $projectQuery)->whereIn('project_status', ['active', 'in_progress'])->count();
        $delayedProjects = (clone $projectQuery)->where('project_status', 'delayed')->count();
        $escalatedProjects = (clone $projectQuery)->where('escalation_status', 'escalated')->count();

        $totalTasks = (clone $taskQuery)->count();
        $delayedTasks = (clone $taskQuery)->where('task_status', 'delayed')->count();
        $blockedTasks = (clone $taskQuery)->where('task_status', 'blocked')->count();
        $avgCompletion = round((float) ((clone $taskQuery)->avg('task_completion_percent') ?? 0), 1);

        $dailyDelayLogs = (int) DB::table('tbl_renovation_daily_delay_logs')
            ->where('is_delete', 0)
            ->count();

        $costOverrun = (float) DB::table('tbl_renovation_cost_tracking')
            ->where('is_delete', 0)
            ->avg('cost_overrun_percent');

        return [
            'total_projects' => $totalProjects,
            'in_progress_projects' => $inProgressProjects,
            'delayed_projects' => $delayedProjects,
            'escalated_projects' => $escalatedProjects,
            'total_tasks' => $totalTasks,
            'delayed_tasks' => $delayedTasks,
            'blocked_tasks' => $blockedTasks,
            'avg_task_completion' => $avgCompletion,
            'daily_delay_logs' => $dailyDelayLogs,
            'avg_cost_overrun_percent' => round($costOverrun ?: 0, 1),
        ];
    }

    private function getRenovationProjectStatusBreakdown(): array
    {
        $labelsMap = [
            'planned' => 'Planned',
            'active' => 'Active',
            'in_progress' => 'In Progress',
            'delayed' => 'Delayed',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ];

        $rows = DB::table('tbl_renovation_projects')
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
            'colors' => ['#adb5bd', '#1cbb8c', '#4aa3ff', '#fcb92c', '#003e6b', '#2d3448'],
        ];
    }

    private function getRenovationTypeBreakdown(): array
    {
        $rows = DB::table('tbl_renovation_projects')
            ->select(DB::raw("COALESCE(NULLIF(renovation_type, ''), 'Unspecified') as label"), DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getRenovationEscalationBreakdown(): array
    {
        $labelsMap = [
            'none' => 'None',
            'escalated' => 'Escalated',
            'resolved' => 'Resolved',
        ];

        $rows = DB::table('tbl_renovation_projects')
            ->select('escalation_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('escalation_status')
            ->pluck('total', 'escalation_status');

        $labels = [];
        $series = [];
        $colors = [];
        $colorsMap = [
            'none' => '#adb5bd',
            'escalated' => '#ff3d60',
            'resolved' => '#1cbb8c',
        ];
        foreach ($labelsMap as $key => $label) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $label;
                $series[] = $count;
                $colors[] = $colorsMap[$key];
            }
        }

        return compact('labels', 'series', 'colors');
    }

    private function getRenovationTaskStatusBreakdown(): array
    {
        $labelsMap = [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'delayed' => 'Delayed',
            'blocked' => 'Blocked',
        ];

        $rows = DB::table('tbl_renovation_tasks')
            ->select('task_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('task_status')
            ->pluck('total', 'task_status');

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
            'colors' => ['#adb5bd', '#4aa3ff', '#1cbb8c', '#fcb92c', '#ff3d60'],
        ];
    }

    private function getRenovationTaskRiskBreakdown(): array
    {
        $order = ['low', 'medium', 'high', 'critical'];
        $labelsMap = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
        $colorsMap = [
            'low' => '#1cbb8c',
            'medium' => '#fcb92c',
            'high' => '#ff3d60',
            'critical' => '#2d3448',
        ];

        $rows = DB::table('tbl_renovation_tasks')
            ->select('risk_level', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->whereNotNull('risk_level')
            ->where('risk_level', '!=', '')
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level');

        $labels = [];
        $series = [];
        $colors = [];
        foreach ($order as $key) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $labelsMap[$key];
                $series[] = $count;
                $colors[] = $colorsMap[$key];
            }
        }

        return compact('labels', 'series', 'colors');
    }

    private function getRenovationTasksByCategory(int $limit = 8): array
    {
        $rows = DB::table('tbl_renovation_tasks')
            ->select(DB::raw("COALESCE(NULLIF(task_category, ''), 'Uncategorised') as label"), DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getRenovationDailyDelayTrend(int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = DB::table('tbl_renovation_daily_delay_logs')
            ->select(DB::raw("DATE_FORMAT(log_date, '%Y-%m') as month_key"), DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->whereNotNull('log_date')
            ->where('log_date', '>=', $start->toDateString())
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

    private function getRecentEscalatedRenovationProjects(int $limit = 5): array
    {
        return DB::table('tbl_renovation_projects')
            ->where('is_delete', 0)
            ->where(function ($query) {
                $query->where('escalation_status', 'escalated')
                    ->orWhere('project_status', 'delayed');
            })
            ->orderByRaw("CASE escalation_status WHEN 'escalated' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'project_code',
                'project_name',
                'zone_department_impacted',
                'project_status',
                'escalation_status',
                'final_handover_date',
            ])
            ->map(fn ($row) => [
                'code' => $row->project_code,
                'name' => $row->project_name,
                'zone' => $row->zone_department_impacted ?? '—',
                'status' => ucfirst(str_replace('_', ' ', $row->project_status ?? '')),
                'escalation' => ucfirst($row->escalation_status ?? 'none'),
                'handover' => !empty($row->final_handover_date)
                    ? date('d M Y', strtotime($row->final_handover_date))
                    : '—',
            ])
            ->all();
    }

    private function getRecentDelayedDepartments(int $limit = 8): array
    {
        if (!Schema::hasTable('tbl_project_departments')) {
            return [];
        }

        $deptTable = $this->departmentsTable();
        $nameCol = $this->departmentNameColumn();

        return DB::table('tbl_project_departments as pd')
            ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
            ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
            ->where('pd.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->where('pd.department_status', 'delay')
            ->orderByDesc('pd.delay_days')
            ->orderByDesc('pd.id')
            ->limit($limit)
            ->get([
                "d.$nameCol as department_name",
                'pd.department_status',
                'pd.delay_days',
                'tp.project_code',
                'tp.project_name',
                'tp.hospital_name',
            ])
            ->map(fn ($row) => [
                'department' => $row->department_name,
                'status' => ucfirst(str_replace('_', ' ', $row->department_status ?? '')),
                'days' => (int) ($row->delay_days ?? 0),
                'project' => trim(($row->project_code ?? '') . ' — ' . ($row->project_name ?? '')),
                'hospital' => $row->hospital_name ?? '—',
            ])
            ->all();
    }

    private function departmentsTable(): string
    {
        return Schema::hasTable('tbl_departments') ? 'tbl_departments' : 'tbl_delay_categories';
    }

    private function departmentNameColumn(): string
    {
        $table = $this->departmentsTable();

        return Schema::hasColumn($table, 'department_name') ? 'department_name' : 'category_name';
    }
}
