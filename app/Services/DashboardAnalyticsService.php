<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsService
{
    protected UserScopeService $userScope;
    protected ?int $zoneFilter = null;
    protected ?int $projectFilter = null;
    protected ?string $dateFrom = null;
    protected ?string $dateTo = null;
    protected bool $scopedProjectIdsResolved = false;
    /** @var int[]|null */
    protected ?array $scopedProjectIdsCache = null;
    /** @var array<string, int>|null */
    protected ?array $projectRollupCountsCache = null;
    /** @var array<string, mixed>|null */
    protected ?array $departmentStatusCountsCache = null;

    public function __construct(UserScopeService $userScope)
    {
        $this->userScope = $userScope;
    }

    /**
     * Load only analytics datasets for widgets the role may see.
     *
     * @param array<string, bool> $visibleWidgets
     */
    public function getDashboardAnalytics(
        array $visibleWidgets,
        ?int $zoneId = null,
        ?int $projectId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        array $sections = ['all']
    ): array {
        $this->zoneFilter = $zoneId;
        $this->projectFilter = $projectId;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $data = [];

        if ($this->shouldLoadSection($sections, 'kpis')) {
            if (!empty($visibleWidgets['m1_kpis'])) {
                $data['kpis'] = $this->getKpis();
            }
            if (!empty($visibleWidgets['m1_task_kpis'])) {
                $data['task_kpis'] = $this->getTaskKpis();
            }
        }

        if ($this->shouldLoadSection($sections, 'charts')) {
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
            if (!empty($visibleWidgets['m1_chart_task_status'])) {
                $data['wizard_task_status'] = $this->getWizardTaskStatusBreakdown();
            }
            if (!empty($visibleWidgets['m1_chart_top_tasks'])) {
                $data['top_wizard_tasks'] = $this->getTopWizardTasksAcrossProjects(8);
            }
        }

        if ($this->shouldLoadSection($sections, 'tables')) {
            if (!empty($visibleWidgets['m1_table_critical'])) {
                $data['recent_delayed_departments'] = $this->getRecentDelayedDepartments(8);
            }
            if (!empty($visibleWidgets['m1_table_dept_open_tasks'])) {
                $data['departments_with_open_tasks'] = $this->getDepartmentsWithOpenTasks(8);
            }
        }

        if ($this->shouldLoadSection($sections, 'zone') && !$this->projectFilter) {
            if (!empty($visibleWidgets['m1_chart_zone'])) {
                $data['zone_metrics'] = $this->getZoneMetrics();
            }
        }

        $this->zoneFilter = null;
        $this->projectFilter = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetRequestCache();

        return $data;
    }

    /** @param  string[]  $sections */
    private function shouldLoadSection(array $sections, string $section): bool
    {
        return in_array('all', $sections, true) || in_array($section, $sections, true);
    }

    private function resetRequestCache(): void
    {
        $this->scopedProjectIdsResolved = false;
        $this->scopedProjectIdsCache = null;
        $this->projectRollupCountsCache = null;
        $this->departmentStatusCountsCache = null;
    }

    public function hasDateFilter(): bool
    {
        return $this->dateFrom !== null || $this->dateTo !== null;
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
        $projectIds = $this->scopedProjectIds();
        $projectQuery = DB::table('tbl_projects')->where('is_delete', 0);
        $this->applyProjectIdFilter($projectQuery, 'id', $projectIds);

        $delayQuery = DB::table('tbl_delay_registers as dr')->where('dr.is_delete', 0);
        if ($projectIds !== null && Schema::hasTable('tbl_project_departments')) {
            $delayQuery->where(function ($q) use ($projectIds) {
                if (empty($projectIds)) {
                    $q->whereRaw('1 = 0');
                    return;
                }
                $q->whereIn('dr.project_id', $projectIds)
                    ->orWhereIn('dr.project_department_id', function ($sub) use ($projectIds) {
                        $sub->select('id')
                            ->from('tbl_project_departments')
                            ->whereIn('project_id', $projectIds)
                            ->where('is_delete', 0);
                    });
            });
        }
        $this->applyDateRangeFilter($delayQuery, 'dr.delay_start_date');

        $pdQuery = DB::table('tbl_project_departments')->where('is_delete', 0);
        $this->applyProjectIdFilter($pdQuery, 'project_id', $projectIds);
        $this->applyDepartmentScope($pdQuery);

        $totalProjects = (clone $projectQuery)->count();
        $rollup = $this->getProjectRollupCounts();
        $activeProjects = $rollup['active'];
        $delayedProjects = $rollup['delayed'];
        $completedProjects = $rollup['completed'];

        $deptCounts = $this->getDepartmentStatusCounts();
        $totalDepartments = $deptCounts['total'];
        $departmentsDelayed = $deptCounts['delay'];
        $departmentsInProgress = $deptCounts['in_progress'];
        $departmentsCompleted = $deptCounts['completed'];

        $deptTable = $this->departmentsTable();
        $masterDepartments = Schema::hasTable($deptTable)
            ? (int) DB::table($deptTable)->where('is_delete', 0)->count()
            : 0;

        $openDelays = $this->countOpenDelayEntries();
        $totalDelays = (clone $delayQuery)->count();
        $avgDelayDays = round((float) ((clone $pdQuery)->avg('delay_days') ?? 0), 1);
        $criticalCount = (clone $delayQuery)->whereIn('severity', ['critical', 'showstopper'])->count();

        $totalDelayCost = (float) DB::table('tbl_delay_financial_impacts as fi')
            ->where('fi.is_delete', 0)
            ->when($projectIds !== null, function ($q) use ($projectIds) {
                $this->applyProjectIdFilter($q, 'fi.project_id', $projectIds);
            })
            ->when($this->hasDateFilter(), function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_delay_registers as dr')
                        ->whereColumn('dr.id', 'fi.delay_register_id')
                        ->where('dr.is_delete', 0);
                    $this->applyDateRangeFilter($sub, 'dr.delay_start_date');
                });
            })
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
            'total_departments' => $totalDepartments,
            'master_departments' => $masterDepartments,
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

        $rows = DB::table('tbl_delay_registers as dr')
            ->select('dr.severity', DB::raw('COUNT(*) as total'))
            ->where('dr.is_delete', 0)
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'dr.project_id', $this->scopedProjectIds());
            })
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, 'dr.delay_start_date'))
            ->groupBy('dr.severity')
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

        if (Schema::hasTable('tbl_project_departments') && !$this->hasDateFilter()) {
            $rows = DB::table('tbl_project_departments as pd')
                ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
                ->where('pd.is_delete', 0)
                ->where('pd.department_status', 'delay')
                ->when($this->scopedProjectIds() !== null, function ($q) {
                    $this->applyProjectIdFilter($q, 'pd.project_id', $this->scopedProjectIds());
                })
                ->tap(fn ($q) => $this->applyDepartmentScope($q, 'pd'))
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
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'dr.project_id', $this->scopedProjectIds());
            })
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, 'dr.delay_start_date'))
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

        $rows = $this->getDepartmentStatusCounts()['by_status'];

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

    /**
     * @return array{total: int, delay: int, in_progress: int, completed: int, by_status: array<string, int>}
     */
    private function getDepartmentStatusCounts(): array
    {
        if ($this->departmentStatusCountsCache !== null) {
            return $this->departmentStatusCountsCache;
        }

        if (!Schema::hasTable('tbl_project_departments')) {
            return $this->departmentStatusCountsCache = [
                'total' => 0,
                'delay' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'by_status' => [],
            ];
        }

        $projectIds = $this->scopedProjectIds();
        $rows = DB::table('tbl_project_departments')
            ->select('department_status', DB::raw('COUNT(*) as total'))
            ->where('is_delete', 0)
            ->when($projectIds !== null, function ($q) use ($projectIds) {
                $this->applyProjectIdFilter($q, 'project_id', $projectIds);
            })
            ->tap(fn ($q) => $this->applyDepartmentScope($q))
            ->groupBy('department_status')
            ->pluck('total', 'department_status');

        $byStatus = [];
        foreach ($rows as $status => $total) {
            $byStatus[(string) $status] = (int) $total;
        }

        return $this->departmentStatusCountsCache = [
            'total' => array_sum($byStatus),
            'delay' => (int) ($byStatus['delay'] ?? 0),
            'in_progress' => (int) (($byStatus['start'] ?? 0) + ($byStatus['in_progress'] ?? 0)),
            'completed' => (int) ($byStatus['completed'] ?? 0),
            'by_status' => $byStatus,
        ];
    }

    private function getProjectStatusBreakdown(): array
    {
        $rollup = $this->getProjectRollupCounts();
        $onHoldQuery = DB::table('tbl_projects')->where('is_delete', 0)->where('project_status', 'on_hold');
        if ($this->scopedProjectIds() !== null) {
            $this->applyProjectIdFilter($onHoldQuery, 'id', $this->scopedProjectIds());
        }
        $onHold = (int) $onHoldQuery->count();

        $labels = [];
        $series = [];
        $colors = ['#1cbb8c', '#fcb92c', '#4aa3ff', '#adb5bd'];
        $buckets = [
            ['label' => 'Active', 'count' => $rollup['active']],
            ['label' => 'Delayed', 'count' => $rollup['delayed']],
            ['label' => 'Completed', 'count' => $rollup['completed']],
            ['label' => 'On Hold', 'count' => $onHold],
        ];

        foreach ($buckets as $i => $bucket) {
            if ($bucket['count'] > 0) {
                $labels[] = $bucket['label'];
                $series[] = $bucket['count'];
            }
        }

        return compact('labels', 'series', 'colors');
    }

    /**
     * Project delayed = any department has department_status = delay.
     * Project completed = has departments and all are completed.
     */
    private function getProjectRollupCounts(): array
    {
        if ($this->projectRollupCountsCache !== null) {
            return $this->projectRollupCountsCache;
        }

        $baseQuery = DB::table('tbl_projects as tp')->where('tp.is_delete', 0);
        $this->applyProjectScope($baseQuery, 'tp');
        $total = (int) (clone $baseQuery)->count();

        if (!Schema::hasTable('tbl_project_departments')) {
            $delayed = (int) (clone $baseQuery)->where('tp.project_status', 'delayed')->count();
            $completed = (int) (clone $baseQuery)->where('tp.project_status', 'completed')->count();
            $onHold = (int) (clone $baseQuery)->where('tp.project_status', 'on_hold')->count();

            return $this->projectRollupCountsCache = [
                'delayed' => $delayed,
                'completed' => $completed,
                'active' => max(0, $total - $delayed - $completed - $onHold),
            ];
        }

        $delayed = (int) (clone $baseQuery)
            ->where('tp.project_status', '!=', 'on_hold')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbl_project_departments as pd')
                    ->whereColumn('pd.project_id', 'tp.id')
                    ->where('pd.is_delete', 0)
                    ->where('pd.department_status', 'delay');
                $this->applyDepartmentScope($query, 'pd');
            })
            ->count();

        $completed = (int) (clone $baseQuery)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbl_project_departments as pd')
                    ->whereColumn('pd.project_id', 'tp.id')
                    ->where('pd.is_delete', 0);
                $this->applyDepartmentScope($query, 'pd');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbl_project_departments as pd')
                    ->whereColumn('pd.project_id', 'tp.id')
                    ->where('pd.is_delete', 0)
                    ->where('pd.department_status', '!=', 'completed');
                $this->applyDepartmentScope($query, 'pd');
            })
            ->count();

        $onHold = (int) (clone $baseQuery)->where('tp.project_status', 'on_hold')->count();
        $active = max(0, $total - $delayed - $completed - $onHold);

        return $this->projectRollupCountsCache = [
            'delayed' => $delayed,
            'completed' => $completed,
            'active' => $active,
        ];
    }

    /**
     * Open delay logs on departments that are currently marked delayed.
     * Excludes legacy/sample register rows not linked to an active delayed department.
     */
    private function countOpenDelayEntries(): int
    {
        $query = DB::table('tbl_delay_registers as dr')
            ->where('dr.is_delete', 0)
            ->whereIn('dr.register_status', ['open', 'in_progress']);

        if (
            Schema::hasTable('tbl_project_departments')
            && Schema::hasColumn('tbl_delay_registers', 'project_department_id')
        ) {
            return (int) $query
                ->whereNotNull('dr.project_department_id')
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.id', 'dr.project_department_id')
                        ->where('pd.is_delete', 0)
                        ->where('pd.department_status', 'delay');
                    $this->applyDepartmentScope($sub, 'pd');
                    if ($this->scopedProjectIds() !== null) {
                        $this->applyProjectIdFilter($sub, 'pd.project_id', $this->scopedProjectIds());
                    }
                })
                ->when($this->scopedProjectIds() !== null, function ($q) {
                    $this->applyProjectIdFilter($q, 'dr.project_id', $this->scopedProjectIds());
                })
                ->tap(fn ($q) => $this->applyDateRangeFilter($q, 'dr.delay_start_date'))
                ->count();
        }

        return (int) $query
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, 'dr.delay_start_date'))
            ->count();
    }

    private function getDelayTrendByMonth(int $defaultMonths = 6): array
    {
        if ($this->dateFrom && $this->dateTo) {
            $start = \Carbon\Carbon::parse($this->dateFrom)->startOfMonth();
            $end = \Carbon\Carbon::parse($this->dateTo)->endOfMonth();
        } elseif ($this->dateFrom) {
            $start = \Carbon\Carbon::parse($this->dateFrom)->startOfMonth();
            $end = now()->endOfMonth();
        } elseif ($this->dateTo) {
            $end = \Carbon\Carbon::parse($this->dateTo)->endOfMonth();
            $start = (clone $end)->subMonths($defaultMonths - 1)->startOfMonth();
        } else {
            $start = now()->subMonths($defaultMonths - 1)->startOfMonth();
            $end = now()->endOfMonth();
        }

        if ($start->gt($end)) {
            $start = (clone $end)->startOfMonth();
        }

        $rows = DB::table('tbl_delay_registers as dr')
            ->select(DB::raw("DATE_FORMAT(dr.delay_start_date, '%Y-%m') as month_key"), DB::raw('COUNT(*) as total'))
            ->where('dr.is_delete', 0)
            ->whereNotNull('dr.delay_start_date')
            ->where('dr.delay_start_date', '>=', $start->toDateString())
            ->where('dr.delay_start_date', '<=', $end->toDateString())
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'dr.project_id', $this->scopedProjectIds());
            })
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->pluck('total', 'month_key');

        $labels = [];
        $series = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $series[] = (int) ($rows[$key] ?? 0);
            $cursor->addMonth();
        }

        return compact('labels', 'series');
    }

    private function getDelaysByHospital(int $limit = 8): array
    {
        $rows = DB::table('tbl_delay_registers as dr')
            ->join('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->where('dr.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'dr.project_id', $this->scopedProjectIds());
            })
            ->tap(fn ($q) => $this->applyDateRangeFilter($q, 'dr.delay_start_date'))
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
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'fi.project_id', $this->scopedProjectIds());
            })
            ->when($this->hasDateFilter(), function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_delay_registers as dr')
                        ->whereColumn('dr.id', 'fi.delay_register_id')
                        ->where('dr.is_delete', 0);
                    $this->applyDateRangeFilter($sub, 'dr.delay_start_date');
                });
            })
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
            ->when($this->hasDateFilter(), function ($q) {
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_delay_registers as dr')
                        ->whereColumn('dr.project_department_id', 'pd.id')
                        ->where('dr.is_delete', 0);
                    $this->applyDateRangeFilter($sub, 'dr.delay_start_date');
                });
            })
            ->when($this->scopedProjectIds() !== null, function ($q) {
                $this->applyProjectIdFilter($q, 'pd.project_id', $this->scopedProjectIds());
            })
            ->tap(fn ($q) => $this->applyProjectScope($q, 'tp'))
            ->tap(fn ($q) => $this->applyDepartmentScope($q, 'pd'))
            ->orderByDesc('pd.delay_days')
            ->orderByDesc('pd.id')
            ->limit($limit)
            ->get([
                'pd.id as pd_id',
                'pd.project_id',
                "d.$nameCol as department_name",
                'pd.department_status',
                'pd.delay_days',
                'tp.project_code',
                'tp.project_name',
                'tp.hospital_name',
            ])
            ->map(fn ($row) => [
                'pd_id' => (int) $row->pd_id,
                'project_id' => (int) $row->project_id,
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

    /**
     * @return \Illuminate\Database\Query\Builder|null
     */
    private function wizardTaskQuery()
    {
        if (!Schema::hasTable('tbl_project_department_tasks') || !Schema::hasTable('tbl_project_departments')) {
            return null;
        }

        $query = DB::table('tbl_project_department_tasks as t')
            ->join('tbl_projects as tp', 'tp.id', '=', 't.project_id')
            ->join('tbl_project_departments as pd', function ($join) {
                $join->on('pd.id', '=', 't.project_department_id')
                    ->where('pd.is_delete', 0);
            })
            ->where('t.is_delete', 0)
            ->whereNull('t.parent_task_id')
            ->where('tp.is_delete', 0);

        $this->applyProjectIdFilter($query, 't.project_id', $this->scopedProjectIds());
        $this->applyProjectScope($query, 'tp');
        $this->applyDepartmentScope($query, 'pd');

        return $query;
    }

    private function getTaskKpis(): array
    {
        $defaults = [
            'total_configured_tasks' => 0,
            'master_tasks' => 0,
            'tasks_not_started' => 0,
            'tasks_in_progress' => 0,
            'tasks_completed' => 0,
            'tasks_on_hold' => 0,
            'tasks_overdue' => 0,
        ];

        $base = $this->wizardTaskQuery();
        if (!$base) {
            return $defaults;
        }

        $today = date('Y-m-d');
        $masterTasks = Schema::hasTable('tbl_tasks')
            ? (int) DB::table('tbl_tasks')->where('is_delete', 0)->where('status', 1)->count()
            : 0;

        $counts = (clone $base)
            ->selectRaw('COUNT(*) as total_configured_tasks')
            ->selectRaw("SUM(CASE WHEN t.task_status = 'not_started' THEN 1 ELSE 0 END) as tasks_not_started")
            ->selectRaw("SUM(CASE WHEN t.task_status = 'in_progress' THEN 1 ELSE 0 END) as tasks_in_progress")
            ->selectRaw("SUM(CASE WHEN t.task_status = 'completed' THEN 1 ELSE 0 END) as tasks_completed")
            ->selectRaw("SUM(CASE WHEN t.task_status = 'on_hold' THEN 1 ELSE 0 END) as tasks_on_hold")
            ->selectRaw(
                "SUM(CASE WHEN t.task_status != 'completed' AND t.planned_end_date IS NOT NULL AND t.planned_end_date < ? THEN 1 ELSE 0 END) as tasks_overdue",
                [$today]
            )
            ->first();

        return [
            'total_configured_tasks' => (int) ($counts->total_configured_tasks ?? 0),
            'master_tasks' => $masterTasks,
            'tasks_not_started' => (int) ($counts->tasks_not_started ?? 0),
            'tasks_in_progress' => (int) ($counts->tasks_in_progress ?? 0),
            'tasks_completed' => (int) ($counts->tasks_completed ?? 0),
            'tasks_on_hold' => (int) ($counts->tasks_on_hold ?? 0),
            'tasks_overdue' => (int) ($counts->tasks_overdue ?? 0),
        ];
    }

    private function getWizardTaskStatusBreakdown(): array
    {
        $base = $this->wizardTaskQuery();
        if (!$base) {
            return ['labels' => [], 'series' => [], 'colors' => []];
        }

        $labelsMap = config('project_department_tasks.statuses', [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ]);
        $colorsMap = [
            'not_started' => '#adb5bd',
            'in_progress' => '#003e6b',
            'completed' => '#1cbb8c',
            'on_hold' => '#fcb92c',
        ];

        $rows = (clone $base)
            ->select('t.task_status', DB::raw('COUNT(*) as total'))
            ->groupBy('t.task_status')
            ->pluck('total', 'task_status');

        $labels = [];
        $series = [];
        $colors = [];
        foreach ($labelsMap as $key => $label) {
            $count = (int) ($rows[$key] ?? 0);
            if ($count > 0) {
                $labels[] = $label;
                $series[] = $count;
                $colors[] = $colorsMap[$key] ?? '#4aa3ff';
            }
        }

        return compact('labels', 'series', 'colors');
    }

    private function getTopWizardTasksAcrossProjects(int $limit = 8): array
    {
        $base = $this->wizardTaskQuery();
        if (!$base) {
            return ['labels' => [], 'series' => [], 'task_ids' => []];
        }

        $rows = (clone $base)
            ->leftJoin('tbl_tasks as mt', 'mt.id', '=', 't.task_id')
            ->select(
                DB::raw('COALESCE(NULLIF(mt.task_name, \'\'), NULLIF(t.task_name, \'\'), \'Task\') as label'),
                DB::raw('MAX(COALESCE(t.task_id, 0)) as task_id'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
            'task_ids' => $rows->pluck('task_id')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    private function getDepartmentsWithOpenTasks(int $limit = 8): array
    {
        if (!Schema::hasTable('tbl_project_department_tasks') || !Schema::hasTable('tbl_project_departments')) {
            return [];
        }

        $deptTable = $this->departmentsTable();
        $nameCol = $this->departmentNameColumn();
        $today = date('Y-m-d');

        $query = DB::table('tbl_project_departments as pd')
            ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
            ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
            ->join('tbl_project_department_tasks as t', function ($join) {
                $join->on('t.project_department_id', '=', 'pd.id')
                    ->where('t.is_delete', 0)
                    ->whereNull('t.parent_task_id')
                    ->whereNotIn('t.task_status', ['completed']);
            })
            ->where('pd.is_delete', 0)
            ->where('tp.is_delete', 0);

        $this->applyProjectIdFilter($query, 'pd.project_id', $this->scopedProjectIds());
        $this->applyProjectScope($query, 'tp');
        $this->applyDepartmentScope($query, 'pd');

        return $query
            ->select(
                'pd.id as pd_id',
                'pd.project_id',
                DB::raw("d.$nameCol as department_name"),
                'tp.project_code',
                'tp.project_name',
                DB::raw('COUNT(t.id) as open_tasks'),
                DB::raw("SUM(CASE WHEN t.planned_end_date IS NOT NULL AND t.planned_end_date < '{$today}' THEN 1 ELSE 0 END) as overdue_tasks")
            )
            ->groupBy('pd.id', 'pd.project_id', "d.$nameCol", 'tp.project_code', 'tp.project_name')
            ->orderByDesc('open_tasks')
            ->orderByDesc('overdue_tasks')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'pd_id' => (int) $row->pd_id,
                'project_id' => (int) $row->project_id,
                'department' => $row->department_name,
                'project' => trim(($row->project_code ?? '') . ' — ' . ($row->project_name ?? '')),
                'open_tasks' => (int) ($row->open_tasks ?? 0),
                'overdue_tasks' => (int) ($row->overdue_tasks ?? 0),
            ])
            ->all();
    }

    private function departmentNameColumn(): string
    {
        $table = $this->departmentsTable();

        return Schema::hasColumn($table, 'department_name') ? 'department_name' : 'category_name';
    }

    /** @return int[]|null null = no scope restriction */
    private function scopedProjectIds(): ?array
    {
        if ($this->scopedProjectIdsResolved) {
            return $this->scopedProjectIdsCache;
        }

        if ($this->projectFilter) {
            $this->scopedProjectIdsCache = $this->isProjectInScope($this->projectFilter)
                ? [$this->projectFilter]
                : [];
        } elseif (!$this->zoneFilter && !$this->userScope->isScopedUser()) {
            $this->scopedProjectIdsCache = null;
        } else {
            $this->scopedProjectIdsCache = $this->resolveScopedProjectIdList();
        }

        $this->scopedProjectIdsResolved = true;

        return $this->scopedProjectIdsCache;
    }

    private function isProjectInScope(int $projectId): bool
    {
        if ($projectId <= 0) {
            return false;
        }

        $query = DB::table('tbl_projects as tp')
            ->where('tp.is_delete', 0)
            ->where('tp.id', $projectId);

        if ($this->zoneFilter) {
            $query->where('tp.zone_id', $this->zoneFilter);
        }

        $this->userScope->applyProjectScope($query, 'tp');

        return $query->exists();
    }

    /** @return int[] */
    private function resolveScopedProjectIdList(): array
    {
        $query = DB::table('tbl_projects as tp')->where('tp.is_delete', 0);
        $this->applyProjectScope($query, 'tp');

        return $query->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
    }

    /** Restrict a query to scoped project ids; empty scope yields no rows. */
    private function applyProjectIdFilter($query, string $column, ?array $projectIds): void
    {
        if ($projectIds === null) {
            return;
        }

        if (empty($projectIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn($column, $projectIds);
    }

    private function applyProjectScope($query, string $alias = 'tp'): void
    {
        if ($this->projectFilter) {
            $query->where($alias . '.id', $this->projectFilter);
        }

        if ($this->zoneFilter) {
            $query->where($alias . '.zone_id', $this->zoneFilter);
        }

        $this->userScope->applyProjectScope($query, $alias);
    }

    private function applyDepartmentScope($query, string $alias = ''): void
    {
        if ($this->userScope->isScopedUser()) {
            $this->userScope->applyProjectDepartmentScope($query, $alias);
        }
    }

    private function applyDateRangeFilter($query, string $column): void
    {
        if ($this->dateFrom) {
            $query->whereDate($column, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($column, '<=', $this->dateTo);
        }
    }

    private function getZoneMetrics(): array
    {
        if (!Schema::hasTable('tbl_zones')) {
            return $this->emptyZoneMetrics();
        }

        $zonesQuery = DB::table('tbl_zones as z')
            ->where('z.is_delete', 0)
            ->where('z.status', 1)
            ->orderBy('z.zone_name');

        if ($this->zoneFilter) {
            $zonesQuery->where('z.id', $this->zoneFilter);
        }

        $zones = $zonesQuery->get(['z.id', 'z.zone_name']);
        if ($zones->isEmpty()) {
            return $this->emptyZoneMetrics();
        }

        $zoneIds = $zones->pluck('id')->map(fn ($id) => (int) $id)->all();
        $projectCounts = $this->getZoneProjectCounts($zoneIds);
        $delayedProjectCounts = $this->getZoneDelayedProjectCounts($zoneIds);
        $deptDelayedCounts = $this->getZoneDelayedDepartmentCounts($zoneIds);

        $labels = [];
        $projects = [];
        $delayed_projects = [];
        $departments_delayed = [];
        $zone_ids = [];
        $emptyTooltip = ['items' => [], 'total' => 0, 'more' => 0];
        $tooltipProjects = [];
        $tooltipDelayedProjects = [];
        $tooltipDepartmentsDelayed = [];

        foreach ($zones as $zone) {
            $zoneId = (int) $zone->id;
            $labels[] = $zone->zone_name;
            $zone_ids[] = $zoneId;
            $projects[] = (int) ($projectCounts[$zoneId] ?? 0);
            $delayed_projects[] = (int) ($delayedProjectCounts[$zoneId] ?? 0);
            $departments_delayed[] = (int) ($deptDelayedCounts[$zoneId] ?? 0);
            $tooltipProjects[] = $emptyTooltip;
            $tooltipDelayedProjects[] = $emptyTooltip;
            $tooltipDepartmentsDelayed[] = $emptyTooltip;
        }

        return [
            'labels' => $labels,
            'projects' => $projects,
            'delayed_projects' => $delayed_projects,
            'departments_delayed' => $departments_delayed,
            'zone_ids' => $zone_ids,
            'tooltip_details' => [
                'projects' => $tooltipProjects,
                'delayed_projects' => $tooltipDelayedProjects,
                'departments_delayed' => $tooltipDepartmentsDelayed,
            ],
        ];
    }

    /** @return array<string, int> */
    private function getZoneProjectCounts(array $zoneIds): array
    {
        if ($zoneIds === []) {
            return [];
        }

        return DB::table('tbl_projects as tp')
            ->select('tp.zone_id', DB::raw('COUNT(*) as total'))
            ->where('tp.is_delete', 0)
            ->whereIn('tp.zone_id', $zoneIds)
            ->tap(fn ($q) => $this->applyProjectScope($q, 'tp'))
            ->groupBy('tp.zone_id')
            ->pluck('total', 'zone_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /** @return array<string, int> */
    private function getZoneDelayedProjectCounts(array $zoneIds): array
    {
        if ($zoneIds === [] || !Schema::hasTable('tbl_project_departments')) {
            return [];
        }

        return DB::table('tbl_projects as tp')
            ->select('tp.zone_id', DB::raw('COUNT(DISTINCT tp.id) as total'))
            ->where('tp.is_delete', 0)
            ->whereIn('tp.zone_id', $zoneIds)
            ->where('tp.project_status', '!=', 'on_hold')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tbl_project_departments as pd')
                    ->whereColumn('pd.project_id', 'tp.id')
                    ->where('pd.is_delete', 0)
                    ->where('pd.department_status', 'delay');
                $this->applyDepartmentScope($sub, 'pd');
            })
            ->tap(fn ($q) => $this->applyProjectScope($q, 'tp'))
            ->groupBy('tp.zone_id')
            ->pluck('total', 'zone_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /** @return array<string, int> */
    private function getZoneDelayedDepartmentCounts(array $zoneIds): array
    {
        if ($zoneIds === [] || !Schema::hasTable('tbl_project_departments')) {
            return [];
        }

        return DB::table('tbl_project_departments as pd')
            ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
            ->select('tp.zone_id', DB::raw('COUNT(*) as total'))
            ->where('pd.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->whereIn('tp.zone_id', $zoneIds)
            ->where('pd.department_status', 'delay')
            ->tap(fn ($q) => $this->applyDepartmentScope($q, 'pd'))
            ->tap(fn ($q) => $this->applyProjectScope($q, 'tp'))
            ->groupBy('tp.zone_id')
            ->pluck('total', 'zone_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function emptyZoneMetrics(): array
    {
        return [
            'labels' => [],
            'projects' => [],
            'delayed_projects' => [],
            'departments_delayed' => [],
            'zone_ids' => [],
            'tooltip_details' => [
                'projects' => [],
                'delayed_projects' => [],
                'departments_delayed' => [],
            ],
        ];
    }

    /**
     * @param  int[]  $projectIds
     * @deprecated Tooltip rows are loaded on demand; kept for optional future endpoint.
     */
    private function buildZoneProjectTooltipBucket(array $projectIds): array
    {
        if ($projectIds === []) {
            return ['items' => [], 'total' => 0, 'more' => 0];
        }

        $rows = DB::table('tbl_projects as tp')
            ->whereIn('tp.id', $projectIds)
            ->where('tp.is_delete', 0)
            ->orderBy('tp.project_code')
            ->limit(8)
            ->get(['tp.project_code', 'tp.project_name', 'tp.hospital_name']);

        $total = count($projectIds);
        $items = $rows->map(function ($row) {
            $label = trim(($row->project_code ?? '') . ' — ' . ($row->project_name ?? ''));
            $hospital = trim($row->hospital_name ?? '');
            if ($hospital !== '') {
                $label .= ' (' . $hospital . ')';
            }

            return $label;
        })->values()->all();

        return [
            'items' => $items,
            'total' => $total,
            'more' => max(0, $total - count($items)),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     */
    private function buildZoneDelayedDepartmentTooltipBucket($rows, int $total): array
    {
        $items = $rows->map(fn ($row) => [
            'department' => (string) ($row->department_name ?? 'Department'),
            'project' => trim(($row->project_code ?? '') . ' — ' . ($row->project_name ?? '')),
            'hospital' => (string) ($row->hospital_name ?? ''),
            'days' => (int) ($row->delay_days ?? 0),
        ])->values()->all();

        return [
            'items' => $items,
            'total' => $total,
            'more' => max(0, $total - count($items)),
        ];
    }
}
