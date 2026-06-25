<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectDepartmentService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_START = 'start';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DELAY = 'delay';
    public const STATUS_COMPLETED = 'completed';

    public function departmentsTable(): string
    {
        return Schema::hasTable('tbl_departments') ? 'tbl_departments' : 'tbl_delay_categories';
    }

    public function departmentNameColumn(): string
    {
        return Schema::hasColumn($this->departmentsTable(), 'department_name') ? 'department_name' : 'category_name';
    }

    public function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_START => 'Ready to Start',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_DELAY => 'Delayed',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    public function statusFilterOptions(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => 'Pending'],
            ['value' => self::STATUS_START, 'label' => 'Ready'],
            ['value' => self::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
            ['value' => self::STATUS_DELAY, 'label' => 'Delayed'],
            ['value' => self::STATUS_COMPLETED, 'label' => 'Completed'],
        ];
    }

    public function statusBadgeHtml(?string $status): string
    {
        $map = [
            self::STATUS_PENDING => ['Pending', 'badge-soft-secondary'],
            self::STATUS_START => ['Ready', 'badge-soft-info'],
            self::STATUS_IN_PROGRESS => ['In Progress', 'badge-soft-primary'],
            self::STATUS_DELAY => ['Delayed', 'badge-soft-warning'],
            self::STATUS_COMPLETED => ['Completed', 'badge-soft-success'],
        ];
        $info = $map[$status] ?? [ucfirst((string) $status), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }

    /**
     * Department workflow panels (delay, financial, attachments) — shared by wizard and SPOC task grid.
     *
     * @return array<string, array{route:string,title_prefix:string,icon:string,label:string,subtitle:string,css_class:string}>
     */
    public function workflowPanels(): array
    {
        return [
            'delay' => [
                'route' => 'projects/wizard/panel/delay',
                'title_prefix' => 'Delay',
                'icon' => 'ri-alarm-warning-line',
                'label' => 'Delay Register',
                'subtitle' => 'Log delays & mitigations',
                'css_class' => 'dept-panel-delay',
            ],
            'financial' => [
                'route' => 'projects/wizard/panel/financial',
                'title_prefix' => 'Financial',
                'icon' => 'ri-money-dollar-circle-line',
                'label' => 'Financial Impact',
                'subtitle' => 'Cost & budget impact',
                'css_class' => 'dept-panel-financial',
            ],
            'attachments' => [
                'route' => 'projects/wizard/panel/attachments',
                'title_prefix' => 'Attachments',
                'icon' => 'ri-file-list-3-line',
                'label' => 'Attachments',
                'subtitle' => 'Documents & files',
                'css_class' => 'dept-panel-attachments',
            ],
        ];
    }

    public function panelUrl(string $type, string $encPdId): string
    {
        $panel = $this->workflowPanels()[$type] ?? null;
        if (!$panel) {
            return '';
        }

        return getProjectUrl($panel['route'] . '/' . $encPdId);
    }

    public function panelTitle(string $type, string $departmentName): string
    {
        $panel = $this->workflowPanels()[$type] ?? null;
        if (!$panel) {
            return $departmentName;
        }

        return $panel['title_prefix'] . ' — ' . $departmentName;
    }

    public function resolveDepartment($encryptedOrId, bool $withProjectContext = false): ?array
    {
        $id = is_numeric($encryptedOrId) ? (int) $encryptedOrId : null;
        if (!$id) {
            try {
                $id = (int) Crypt::decrypt($encryptedOrId);
            } catch (\Exception $e) {
                return null;
            }
        }

        $nameCol = $this->departmentNameColumn();
        $deptTable = $this->departmentsTable();

        $query = DB::table('tbl_project_departments as pd')
            ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
            ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
            ->leftJoin('tbl_zones as tz', 'tz.id', '=', 'tp.zone_id')
            ->where('pd.id', $id)
            ->where('pd.is_delete', 0)
            ->where('tp.is_delete', 0);

        if (Schema::hasTable('tbl_locations')) {
            $query->leftJoin('tbl_locations as tl', 'tl.id', '=', 'tp.location_id');
        }

        $columns = [
            'pd.*',
            DB::raw("d.$nameCol as department_name"),
            'd.description as department_description',
            'tp.project_code',
            'tp.project_name',
            'tp.hospital_name',
            'tz.zone_name',
            Schema::hasTable('tbl_locations') ? 'tl.location_name' : DB::raw("'' as location_name"),
        ];

        $row = $query->first($columns);
        if (!$row) {
            return null;
        }

        if (!$withProjectContext) {
            return (array) $row;
        }

        return [
            'department' => (array) $row,
            'project' => [
                'project_code' => $row->project_code,
                'project_name' => $row->project_name,
                'hospital_name' => $row->hospital_name,
                'zone_name' => $row->zone_name ?? '',
                'location_name' => $row->location_name ?? '',
            ],
        ];
    }

    public function getProjectDepartments(int $projectId): array
    {
        $nameCol = $this->departmentNameColumn();
        $deptTable = $this->departmentsTable();

        return DB::table('tbl_project_departments as pd')
            ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
            ->where('pd.project_id', $projectId)
            ->where('pd.is_delete', 0)
            ->orderBy('pd.sort_order')
            ->orderBy('pd.id')
            ->get([
                'pd.*',
                DB::raw("d.$nameCol as department_name"),
                'd.description as department_description',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function syncProjectDepartments(int $projectId, array $orderedDepartmentIds, array $parallelFlags = [], array $spocAssignments = []): void
    {
        $userId = Auth::id();
        $now = current_datetime();
        $hasParallelColumn = Schema::hasColumn('tbl_project_departments', 'allow_parallel_next');
        $existing = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->get()
            ->keyBy('department_id');

        $sort = 1;
        $total = count(array_filter(array_map('intval', $orderedDepartmentIds)));
        foreach ($orderedDepartmentIds as $departmentId) {
            $departmentId = (int) $departmentId;
            if ($departmentId <= 0) {
                continue;
            }

            $setup = $spocAssignments[$departmentId] ?? $spocAssignments[(string) $departmentId] ?? [];
            $allowParallel = ($hasParallelColumn && $sort < $total && !empty($parallelFlags[$departmentId])) ? 1 : 0;
            if ($hasParallelColumn && isset($setup['allow_parallel_next'])) {
                $allowParallel = $sort < $total && !empty($setup['allow_parallel_next']) ? 1 : 0;
            }

            $spocFields = $this->resolveSpocFieldsFromSetup($setup);

            if (isset($existing[$departmentId])) {
                $update = array_merge([
                    'sort_order' => $sort,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                ], $spocFields);
                if ($hasParallelColumn) {
                    $update['allow_parallel_next'] = $allowParallel;
                }
                DB::table('tbl_project_departments')
                    ->where('id', $existing[$departmentId]->id)
                    ->update($update);
            } else {
                $insert = array_merge([
                    'project_id' => $projectId,
                    'department_id' => $departmentId,
                    'sort_order' => $sort,
                    'department_status' => self::STATUS_PENDING,
                    'created_by' => $userId,
                    'created_on' => $now,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ], $spocFields);
                if ($hasParallelColumn) {
                    $insert['allow_parallel_next'] = $allowParallel;
                }
                DB::table('tbl_project_departments')->insert($insert);
            }
            $sort++;
        }

        DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->whereNotIn('department_id', $orderedDepartmentIds)
            ->update([
                'is_delete' => 1,
                'updated_by' => $userId,
                'updated_on' => $now,
            ]);

        $this->recomputeDepartmentAvailability($projectId);
        $this->syncProjectRollupStatus($projectId);
    }

    /** @param array<string, mixed> $setup */
    private function resolveSpocFieldsFromSetup(array $setup): array
    {
        $fields = [];
        $touchPlannedDates = !empty($setup['_touch_planned_dates']);

        if ($touchPlannedDates) {
            $fields['planned_start_date'] = $this->normalizeOptionalDate($setup['planned_start_date'] ?? null);
            $fields['planned_end_date'] = $this->normalizeOptionalDate($setup['planned_end_date'] ?? null);
        } else {
            if (array_key_exists('planned_start_date', $setup)) {
                $normalized = $this->normalizeOptionalDate($setup['planned_start_date']);
                if ($normalized !== null) {
                    $fields['planned_start_date'] = $normalized;
                }
            }
            if (array_key_exists('planned_end_date', $setup)) {
                $normalized = $this->normalizeOptionalDate($setup['planned_end_date']);
                if ($normalized !== null) {
                    $fields['planned_end_date'] = $normalized;
                }
            }
        }

        if (!empty($setup['spoc_user_id'])) {
            $spocUserId = (int) $setup['spoc_user_id'];
            $spocUser = DB::table('tbl_user')->where('id', $spocUserId)->where('status', ACTIVE)->first();
            if ($spocUser) {
                $fields['spoc_user_id'] = $spocUserId;
                $fields['spoc_name'] = trim(($spocUser->first_name ?? '') . ' ' . ($spocUser->last_name ?? ''));
            }
        } elseif (array_key_exists('spoc_user_id', $setup) && ($setup['spoc_user_id'] === '' || $setup['spoc_user_id'] === null)) {
            $fields['spoc_user_id'] = null;
            $fields['spoc_name'] = null;
        } elseif (!empty($setup['spoc_name'])) {
            $fields['spoc_name'] = trim((string) $setup['spoc_name']);
        }

        return $fields;
    }

    private function normalizeOptionalDate($value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    /**
     * When the previous department is sequential, current planned dates must not precede the previous range.
     *
     * @param  array<int>|null  $orderedDepartmentIds
     * @return string HTML error fragment (<li>...</li>) or empty
     */
    public function validateSequentialDepartmentDates(
        int $projectId,
        int $departmentId,
        ?string $plannedStart,
        ?string $plannedEnd,
        ?array $orderedDepartmentIds = null
    ): string {
        if ($orderedDepartmentIds === null) {
            $orderedDepartmentIds = DB::table('tbl_project_departments')
                ->where('project_id', $projectId)
                ->where('is_delete', 0)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('department_id')
                ->map(static fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $idx = array_search($departmentId, $orderedDepartmentIds, true);
        if ($idx === false || $idx <= 0) {
            return '';
        }

        $prevDeptId = (int) $orderedDepartmentIds[$idx - 1];
        $prevRow = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('department_id', $prevDeptId)
            ->where('is_delete', 0)
            ->first();

        if (!$prevRow || $this->allowsParallelNext((array) $prevRow)) {
            return '';
        }

        $minDate = $this->normalizeOptionalDate($prevRow->planned_end_date ?? null);
        if (!$minDate) {
            return '';
        }

        $prevResolved = $this->resolveDepartment((int) $prevRow->id);
        $prevName = $prevResolved['department_name'] ?? 'previous department';

        $start = $this->normalizeOptionalDate($plannedStart);
        $end = $this->normalizeOptionalDate($plannedEnd);

        if ($start && $start < $minDate) {
            return '<li>Planned start must be on or after ' . e($minDate)
                . ' (sequential step after ' . e($prevName) . ')</li>';
        }
        if ($end && $end < $minDate) {
            return '<li>Planned end must be on or after ' . e($minDate)
                . ' (sequential step after ' . e($prevName) . ')</li>';
        }

        return '';
    }

    /**
     * Department planned dates must not begin before the project planned start.
     * End date may extend past project planned completion when delays revise the timeline.
     *
     * @return string HTML error fragment (<li>...</li>) or empty
     */
    public function validateDepartmentDatesAgainstProject(
        int $projectId,
        ?string $plannedStart,
        ?string $plannedEnd
    ): string {
        $project = DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->first(['planned_start_date']);

        if (!$project) {
            return '';
        }

        $projectStart = $this->normalizeOptionalDate($project->planned_start_date ?? null);
        $start = $this->normalizeOptionalDate($plannedStart);
        $end = $this->normalizeOptionalDate($plannedEnd);

        if ($projectStart && $start && $start < $projectStart) {
            return '<li>Department planned start cannot be earlier than the project planned start date ('
                . e($projectStart) . ')</li>';
        }
        if ($projectStart && $end && $end < $projectStart) {
            return '<li>Department planned end cannot be earlier than the project planned start date ('
                . e($projectStart) . ')</li>';
        }

        return '';
    }

    public function hasDepartmentSpoc($row): bool
    {
        $row = (array) $row;
        if (!empty($row['spoc_user_id'])) {
            return true;
        }

        return trim((string) ($row['spoc_name'] ?? '')) !== '';
    }

    public function updateDepartmentRow(int $projectDepartmentId, array $data): bool
    {
        $data['updated_by'] = Auth::id();
        $data['updated_on'] = current_datetime();

        return (bool) DB::table('tbl_project_departments')
            ->where('id', $projectDepartmentId)
            ->where('is_delete', 0)
            ->update($data);
    }

    public function markCompleted(int $projectDepartmentId): array
    {
        $row = DB::table('tbl_project_departments')->where('id', $projectDepartmentId)->where('is_delete', 0)->first();
        if (!$row) {
            return ['error' => 1, 'msg' => 'Department row not found'];
        }

        if (!$this->canEditDepartment((array) $row)) {
            return ['error' => 1, 'msg' => 'Complete previous departments before marking this one complete'];
        }

        $this->updateDepartmentRow($projectDepartmentId, [
            'department_status' => self::STATUS_COMPLETED,
            'actual_end_date' => $row->actual_end_date ?: date('Y-m-d'),
        ]);

        $this->activateNextDepartment((int) $row->project_id);
        $this->syncProjectRollupStatus((int) $row->project_id);
        $this->ensureWizardExecutionStep((int) $row->project_id);

        return ['error' => 0, 'msg' => 'Department marked completed'];
    }

    /** Keep wizard on Execution step once department workflow has started. */
    public function ensureWizardExecutionStep(int $projectId): void
    {
        if ($projectId <= 0 || !Schema::hasColumn('tbl_projects', 'wizard_step')) {
            return;
        }

        $hasDepartments = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->exists();

        if (!$hasDepartments) {
            return;
        }

        DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->where('wizard_step', '<', 3)
            ->update([
                'wizard_step' => 3,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);
    }

    public function setDepartmentDelay(int $projectDepartmentId): void
    {
        $row = DB::table('tbl_project_departments')->where('id', $projectDepartmentId)->where('is_delete', 0)->first();
        if (!$row || $row->department_status === self::STATUS_COMPLETED) {
            return;
        }

        if (!in_array($row->department_status, [self::STATUS_IN_PROGRESS, self::STATUS_DELAY, self::STATUS_START], true)) {
            return;
        }

        $this->updateDepartmentRow($projectDepartmentId, ['department_status' => self::STATUS_DELAY]);
        $this->syncProjectRollupStatus((int) $row->project_id);
    }

    public function canEditDepartment(array $row): bool
    {
        if (($row['department_status'] ?? '') === self::STATUS_PENDING) {
            return false;
        }

        $projectId = (int) ($row['project_id'] ?? 0);
        $sortOrder = (int) ($row['sort_order'] ?? 0);
        if ($projectId <= 0 || $sortOrder <= 1) {
            return $sortOrder > 0;
        }

        $prev = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->where('sort_order', $sortOrder - 1)
            ->first();

        if (!$prev) {
            return true;
        }

        if ($prev->department_status === self::STATUS_COMPLETED) {
            return true;
        }

        return $this->allowsParallelNext((array) $prev);
    }

    public function isDepartmentLocked(array $row): bool
    {
        $status = $row['department_status'] ?? self::STATUS_PENDING;
        if ($status === self::STATUS_PENDING) {
            return true;
        }

        if ($status === self::STATUS_COMPLETED) {
            return false;
        }

        return !$this->canEditDepartment($row);
    }

    public function isAccordionExpandable(array $row, array $allRows = []): bool
    {
        return !$this->isDepartmentLocked($row);
    }

    public function allowsParallelNext(array $row): bool
    {
        if (!Schema::hasColumn('tbl_project_departments', 'allow_parallel_next')) {
            return false;
        }

        return !empty($row['allow_parallel_next']);
    }

    public function syncAllProjectRollupStatuses(): void
    {
        $projectIds = DB::table('tbl_projects')->where('is_delete', 0)->pluck('id');
        foreach ($projectIds as $projectId) {
            $this->syncProjectRollupStatus((int) $projectId);
        }
    }

    public function syncProjectRollupStatus(int $projectId): void
    {
        $rows = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->get();

        if ($rows->isEmpty()) {
            $project = DB::table('tbl_projects')->where('id', $projectId)->where('is_delete', 0)->first();
            if ($project && ($project->project_status ?? '') !== 'on_hold') {
                DB::table('tbl_projects')
                    ->where('id', $projectId)
                    ->where('is_delete', 0)
                    ->update([
                        'project_status' => 'active',
                        'updated_by' => Auth::id(),
                        'updated_on' => current_datetime(),
                    ]);
            }

            return;
        }

        $hasDelay = $rows->contains(fn ($r) => $r->department_status === self::STATUS_DELAY);
        $allCompleted = $rows->every(fn ($r) => $r->department_status === self::STATUS_COMPLETED);

        $projectStatus = 'active';
        if ($allCompleted) {
            $projectStatus = 'completed';
        } elseif ($hasDelay) {
            $projectStatus = 'delayed';
        } elseif ($rows->contains(fn ($r) => in_array($r->department_status, [self::STATUS_START, self::STATUS_IN_PROGRESS], true))) {
            $projectStatus = 'active';
        }

        DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->update([
                'project_status' => $projectStatus,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);
    }

    public function recomputeDepartmentAvailability(int $projectId): void
    {
        $rows = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $prev = null;
        foreach ($rows as $row) {
            if ($row->department_status === self::STATUS_COMPLETED) {
                $prev = $row;
                continue;
            }

            $canActivate = $prev === null
                || $prev->department_status === self::STATUS_COMPLETED
                || $this->allowsParallelNext((array) $prev);

            if ($canActivate) {
                if ($row->department_status === self::STATUS_PENDING) {
                    DB::table('tbl_project_departments')->where('id', $row->id)->update([
                        'department_status' => self::STATUS_START,
                        'updated_by' => Auth::id(),
                        'updated_on' => current_datetime(),
                    ]);
                    $row = (object) array_merge((array) $row, ['department_status' => self::STATUS_START]);
                }
            } elseif (!in_array($row->department_status, [self::STATUS_IN_PROGRESS, self::STATUS_DELAY], true)) {
                DB::table('tbl_project_departments')->where('id', $row->id)->update([
                    'department_status' => self::STATUS_PENDING,
                    'updated_by' => Auth::id(),
                    'updated_on' => current_datetime(),
                ]);
                $row = (object) array_merge((array) $row, ['department_status' => self::STATUS_PENDING]);
            }

            $prev = $row;
        }
    }

    private function activateNextDepartment(int $projectId): void
    {
        $this->recomputeDepartmentAvailability($projectId);
    }
}
