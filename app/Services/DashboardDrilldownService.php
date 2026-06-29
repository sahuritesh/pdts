<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDrilldownService
{
    /**
     * KPI card drill targets for the dashboard blade.
     *
     * @return array<string, string>
     */
    public function buildKpiLinks(?int $zoneId = null, ?int $projectId = null): array
    {
        $filters = $this->dashboardFilterParams($zoneId, $projectId);

        return [
            'total_projects' => $projectId
                ? $this->projectWizardUrl($projectId)
                : $this->projectsListUrl($filters),
            'open_delays' => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
            'departments_delayed' => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
            'total_delay_cost' => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
            'delayed_projects' => $this->projectsListUrl(array_merge($filters, ['rollup_status' => 'delayed'])),
            'departments_in_progress' => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'active_work'])),
            'departments_completed' => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'completed'])),
            'total_departments' => $this->departmentsMasterUrl(),
            'total_configured_tasks' => $this->wizardTasksDrillUrl($filters),
            'master_tasks' => $this->tasksMasterUrl(),
            'tasks_not_started' => $this->wizardTasksDrillUrl(array_merge($filters, ['task_status' => 'not_started'])),
            'tasks_in_progress' => $this->wizardTasksDrillUrl(array_merge($filters, ['task_status' => 'in_progress'])),
            'tasks_completed' => $this->wizardTasksDrillUrl(array_merge($filters, ['task_status' => 'completed'])),
            'tasks_on_hold' => $this->wizardTasksDrillUrl(array_merge($filters, ['task_status' => 'on_hold'])),
            'tasks_overdue' => $this->wizardTasksDrillUrl(array_merge($filters, ['task_overdue' => 1])),
        ];
    }

    /**
     * Add drill_urls (and keys where needed) to analytics chart payloads.
     */
    public function attachChartDrillUrls(array $analytics, ?int $zoneId = null, ?int $projectId = null): array
    {
        $filters = $this->dashboardFilterParams($zoneId, $projectId);

        if (!empty($analytics['project_status'])) {
            $labelToRollup = [
                'Active' => 'active',
                'Delayed' => 'delayed',
                'Completed' => 'completed',
                'On Hold' => 'on_hold',
            ];
            $analytics['project_status']['drill_urls'] = [];
            foreach ($analytics['project_status']['labels'] ?? [] as $label) {
                $key = $labelToRollup[$label] ?? '';
                if ($key === 'on_hold') {
                    $analytics['project_status']['drill_urls'][] = $this->projectsListUrl(array_merge($filters, ['project_status' => 'on_hold']));
                    continue;
                }
                $analytics['project_status']['drill_urls'][] = $key !== ''
                    ? $this->projectsListUrl(array_merge($filters, ['rollup_status' => $key]))
                    : $this->projectsListUrl($filters);
            }
        }

        if (!empty($analytics['department_status'])) {
            $statusKeys = ['pending', 'start', 'in_progress', 'delay', 'completed'];
            $labelToKey = [
                'Pending' => 'pending',
                'Ready' => 'start',
                'In Progress' => 'in_progress',
                'Delayed' => 'delay',
                'Completed' => 'completed',
            ];
            $analytics['department_status']['drill_urls'] = [];
            foreach ($analytics['department_status']['labels'] ?? [] as $label) {
                $key = $labelToKey[$label] ?? '';
                $filter = $key === 'start' || $key === 'in_progress' ? 'active_work' : $key;
                $analytics['department_status']['drill_urls'][] = $key !== ''
                    ? $this->departmentTasksUrl(array_merge($filters, ['status_filter' => $filter]))
                    : $this->departmentTasksUrl($filters);
            }
        }

        if (!empty($analytics['delays_by_severity'])) {
            $analytics['delays_by_severity']['drill_urls'] = array_map(
                fn () => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
                $analytics['delays_by_severity']['labels'] ?? []
            );
        }

        if (!empty($analytics['delays_by_category'])) {
            $analytics['delays_by_category']['drill_urls'] = array_map(
                fn ($label) => $this->departmentTasksUrl(array_merge($filters, [
                    'status_filter' => 'delay',
                    'search' => $label,
                ])),
                $analytics['delays_by_category']['labels'] ?? []
            );
        }

        if (!empty($analytics['delays_by_hospital'])) {
            $hospitalIds = $this->resolveHospitalIdsByName($analytics['delays_by_hospital']['labels'] ?? []);
            $analytics['delays_by_hospital']['hospital_ids'] = $hospitalIds;
            $analytics['delays_by_hospital']['drill_urls'] = array_map(
                fn ($hospitalId) => $hospitalId
                    ? $this->projectsListUrl(array_merge($filters, ['hospital' => $hospitalId]))
                    : $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
                $hospitalIds
            );
        }

        if (!empty($analytics['financial_impact'])) {
            $analytics['financial_impact']['drill_urls'] = [
                $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
                $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
            ];
        }

        if (!empty($analytics['delay_trend'])) {
            $analytics['delay_trend']['drill_urls'] = array_map(
                fn () => $this->departmentTasksUrl(array_merge($filters, ['status_filter' => 'delay'])),
                $analytics['delay_trend']['labels'] ?? []
            );
        }

        if (!empty($analytics['zone_metrics'])) {
            $zoneIds = $analytics['zone_metrics']['zone_ids'] ?? [];
            $analytics['zone_metrics']['drill_urls'] = [
                'projects' => array_map(
                    fn ($id) => $this->projectsListUrl(['zone_id' => $id]),
                    $zoneIds
                ),
                'delayed_projects' => array_map(
                    fn ($id) => $this->projectsListUrl(['zone_id' => $id, 'rollup_status' => 'delayed']),
                    $zoneIds
                ),
                'departments_delayed' => array_map(
                    fn ($id) => $this->departmentTasksUrl(['zone_id' => $id, 'status_filter' => 'delay']),
                    $zoneIds
                ),
            ];
        }

        if (!empty($analytics['recent_delayed_departments'])) {
            $analytics['recent_delayed_departments'] = array_map(function ($row) {
                $row['url'] = $this->departmentRowUrl(
                    (int) ($row['pd_id'] ?? 0),
                    (int) ($row['project_id'] ?? 0)
                );

                return $row;
            }, $analytics['recent_delayed_departments']);
        }

        if (!empty($analytics['wizard_task_status'])) {
            $labelToKey = array_flip(config('project_department_tasks.statuses', []));
            $analytics['wizard_task_status']['drill_urls'] = [];
            foreach ($analytics['wizard_task_status']['labels'] ?? [] as $label) {
                $key = $labelToKey[$label] ?? '';
                $analytics['wizard_task_status']['drill_urls'][] = $key !== ''
                    ? $this->wizardTasksDrillUrl(array_merge($filters, ['task_status' => $key]))
                    : $this->wizardTasksDrillUrl($filters);
            }
        }

        if (!empty($analytics['top_wizard_tasks'])) {
            $analytics['top_wizard_tasks']['drill_urls'] = array_map(
                fn ($label) => $this->tasksMasterUrl(['search' => $label]),
                $analytics['top_wizard_tasks']['labels'] ?? []
            );
        }

        if (!empty($analytics['departments_with_open_tasks'])) {
            $analytics['departments_with_open_tasks'] = array_map(function ($row) {
                $row['url'] = $this->departmentRowUrl(
                    (int) ($row['pd_id'] ?? 0),
                    (int) ($row['project_id'] ?? 0)
                );

                return $row;
            }, $analytics['departments_with_open_tasks']);
        }

        return $analytics;
    }

    public function departmentRowUrl(int $projectDepartmentId, int $projectId): string
    {
        if ($projectId > 0 && (permissionexists('projects') === '1' || permissionexists('my_projects') === '1')) {
            return getProjectUrl('projects/wizard/' . Crypt::encrypt($projectId) . '?step=execution');
        }

        if ($projectDepartmentId > 0 && $this->canUseDepartmentTasks()) {
            return $this->departmentTasksUrl(['status_filter' => 'delay']);
        }

        return $this->departmentTasksUrl(['status_filter' => 'delay']);
    }

    public function projectsListUrl(array $params = []): string
    {
        if (permissionexists('projects') === '1') {
            return $this->buildListUrl('projects-list', $params);
        }
        if (permissionexists('my_projects') === '1'
            || permissionexists('spoc_project_access') === '1'
            || permissionexists('spoc_department_access') === '1') {
            return $this->buildListUrl('my-projects-list', $params);
        }

        return getProjectUrl('projects-list');
    }

    public function departmentTasksUrl(array $params = []): string
    {
        if ($this->canUseDepartmentTasks()) {
            return $this->buildListUrl('spoc-tasks-list', $params);
        }

        $status = $params['status_filter'] ?? '';
        if ($status === 'delay' || $status === 'active_work') {
            return $this->projectsListUrl(['rollup_status' => 'delayed']);
        }
        if ($status === 'completed') {
            return $this->projectsListUrl(['rollup_status' => 'completed']);
        }

        return $this->projectsListUrl($this->dashboardFilterParams($params['zone_id'] ?? null, $params['project_id'] ?? null));
    }

    public function projectWizardUrl(int $projectId): string
    {
        if ($projectId <= 0) {
            return $this->projectsListUrl();
        }

        return getProjectUrl('projects/wizard/' . Crypt::encrypt($projectId));
    }

    public function departmentsMasterUrl(): string
    {
        if (function_exists('modulePermissionExists') && modulePermissionExists('departments')) {
            return getProjectUrl('departments-list');
        }

        return $this->departmentTasksUrl();
    }

    public function tasksMasterUrl(array $params = []): string
    {
        if (function_exists('modulePermissionExists') && modulePermissionExists('tasks')) {
            return $this->buildListUrl('tasks-list', $params);
        }

        return $this->wizardTasksDrillUrl($params);
    }

    public function wizardTasksDrillUrl(array $params = []): string
    {
        if ($this->canUseDepartmentTasks()) {
            return $this->buildListUrl('spoc-tasks-list', $params);
        }

        if (permissionexists('projects') === '1'
            || permissionexists('my_projects') === '1'
            || permissionexists('spoc_project_access') === '1') {
            return $this->projectsListUrl($this->dashboardFilterParams($params['zone_id'] ?? null, $params['project_id'] ?? null));
        }

        return getProjectUrl('dashboard');
    }

    private function canUseDepartmentTasks(): bool
    {
        return permissionexists('spoc_tasks') === '1'
            || permissionexists('spoc_department_access') === '1';
    }

    private function buildListUrl(string $route, array $params): string
    {
        $query = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === 'All') {
                continue;
            }
            $query['gf_' . $key] = $value;
        }

        $url = getProjectUrl($route);
        if ($query === []) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }

    /** @return array<string, int> */
    private function dashboardFilterParams(?int $zoneId, ?int $projectId = null): array
    {
        $params = [];
        if ($zoneId) {
            $params['zone_id'] = $zoneId;
        }
        if ($projectId) {
            $params['project_id'] = $projectId;
        }

        return $params;
    }

    /**
     * @param  array<int, string>  $names
     * @return array<int, int|null>
     */
    private function resolveHospitalIdsByName(array $names): array
    {
        if (!Schema::hasTable('tbl_hospitals') || $names === []) {
            return array_fill(0, count($names), null);
        }

        $map = DB::table('tbl_hospitals')
            ->where('is_delete', 0)
            ->whereIn('hospital_name', $names)
            ->pluck('id', 'hospital_name');

        return array_map(
            fn ($name) => isset($map[$name]) ? (int) $map[$name] : null,
            $names
        );
    }
}
