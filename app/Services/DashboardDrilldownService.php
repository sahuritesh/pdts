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
    public function buildKpiLinks(?int $zoneId = null): array
    {
        $zone = $this->zoneParams($zoneId);

        return [
            'total_projects' => $this->projectsListUrl($zone),
            'open_delays' => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
            'departments_delayed' => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
            'total_delay_cost' => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
            'delayed_projects' => $this->projectsListUrl(array_merge($zone, ['rollup_status' => 'delayed'])),
            'departments_in_progress' => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'active_work'])),
            'departments_completed' => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'completed'])),
            'total_departments' => $this->departmentsMasterUrl(),
        ];
    }

    /**
     * Add drill_urls (and keys where needed) to analytics chart payloads.
     */
    public function attachChartDrillUrls(array $analytics, ?int $zoneId = null): array
    {
        $zone = $this->zoneParams($zoneId);

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
                    $analytics['project_status']['drill_urls'][] = $this->projectsListUrl(array_merge($zone, ['project_status' => 'on_hold']));
                    continue;
                }
                $analytics['project_status']['drill_urls'][] = $key !== ''
                    ? $this->projectsListUrl(array_merge($zone, ['rollup_status' => $key]))
                    : $this->projectsListUrl($zone);
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
                    ? $this->departmentTasksUrl(array_merge($zone, ['status_filter' => $filter]))
                    : $this->departmentTasksUrl($zone);
            }
        }

        if (!empty($analytics['delays_by_severity'])) {
            $analytics['delays_by_severity']['drill_urls'] = array_map(
                fn () => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
                $analytics['delays_by_severity']['labels'] ?? []
            );
        }

        if (!empty($analytics['delays_by_category'])) {
            $analytics['delays_by_category']['drill_urls'] = array_map(
                fn ($label) => $this->departmentTasksUrl(array_merge($zone, [
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
                    ? $this->projectsListUrl(array_merge($zone, ['hospital' => $hospitalId]))
                    : $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
                $hospitalIds
            );
        }

        if (!empty($analytics['financial_impact'])) {
            $analytics['financial_impact']['drill_urls'] = [
                $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
                $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
            ];
        }

        if (!empty($analytics['delay_trend'])) {
            $analytics['delay_trend']['drill_urls'] = array_map(
                fn () => $this->departmentTasksUrl(array_merge($zone, ['status_filter' => 'delay'])),
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

        return $this->projectsListUrl($this->zoneParams($params['zone_id'] ?? null));
    }

    public function departmentsMasterUrl(): string
    {
        if (function_exists('modulePermissionExists') && modulePermissionExists('departments')) {
            return getProjectUrl('departments-list');
        }

        return $this->departmentTasksUrl();
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

    /** @return array<string, int|string|null> */
    private function zoneParams(?int $zoneId): array
    {
        return $zoneId ? ['zone_id' => $zoneId] : [];
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
