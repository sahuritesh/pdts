<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectDepartmentTaskService
{
    public const KIND_STANDARD = 'standard';
    public const KIND_LINKED_DEPARTMENT = 'linked_department';

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ON_HOLD = 'on_hold';

    public const AUDIT_ENTITY = 'project_department_task';

    public function __construct(
        protected ProjectDepartmentService $projectDepartmentService,
        protected AuditTrailService $auditTrail
    ) {
    }

    public function kinds(): array
    {
        return config('project_department_tasks.kinds', []);
    }

    public function statusLabels(): array
    {
        return config('project_department_tasks.statuses', []);
    }

    public function statusBadgeHtml(?string $status): string
    {
        $map = config('project_department_tasks.status_badges', []);
        $info = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'badge-soft-secondary'];

        return '<span class="badge ' . e($info[1]) . '">' . e($info[0]) . '</span>';
    }

    public function tableExists(): bool
    {
        return Schema::hasTable('tbl_project_department_tasks');
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getTasksGroupedByProjectDepartment(int $projectId): array
    {
        if (!$this->tableExists() || $projectId <= 0) {
            return [];
        }

        $grouped = [];
        foreach ($this->getTasksForProject($projectId) as $task) {
            $pdId = (int) ($task['project_department_id'] ?? 0);
            if ($pdId <= 0) {
                continue;
            }
            $grouped[$pdId][] = $task;
        }

        return $grouped;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTasksForProjectDepartment(int $projectDepartmentId, bool $rootsOnly = true): array
    {
        if (!$this->tableExists() || $projectDepartmentId <= 0) {
            return [];
        }

        $query = DB::table('tbl_project_department_tasks as t')
            ->leftJoin('tbl_departments as ld', 'ld.id', '=', 't.linked_department_id')
            ->leftJoin('tbl_project_departments as lpd', 'lpd.id', '=', 't.linked_project_department_id')
            ->leftJoin('tbl_departments as lpd_d', 'lpd_d.id', '=', 'lpd.department_id')
            ->where('t.project_department_id', $projectDepartmentId)
            ->where('t.is_delete', 0)
            ->orderBy('t.sort_order')
            ->orderBy('t.id');

        if ($rootsOnly) {
            $query->whereNull('t.parent_task_id');
        }

        $nameCol = $this->projectDepartmentService->departmentNameColumn();

        return $query->get([
            't.*',
            DB::raw("ld.$nameCol as linked_department_name"),
            DB::raw("lpd_d.$nameCol as linked_project_department_name"),
            'lpd.department_status as linked_department_status',
        ])->map(fn ($row) => $this->formatTaskRow((array) $row))->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTasksForProject(int $projectId): array
    {
        if (!$this->tableExists() || $projectId <= 0) {
            return [];
        }

        $nameCol = $this->projectDepartmentService->departmentNameColumn();

        return DB::table('tbl_project_department_tasks as t')
            ->leftJoin('tbl_departments as ld', 'ld.id', '=', 't.linked_department_id')
            ->leftJoin('tbl_project_departments as lpd', 'lpd.id', '=', 't.linked_project_department_id')
            ->leftJoin('tbl_departments as lpd_d', 'lpd_d.id', '=', 'lpd.department_id')
            ->where('t.project_id', $projectId)
            ->where('t.is_delete', 0)
            ->whereNull('t.parent_task_id')
            ->orderBy('t.project_department_id')
            ->orderBy('t.sort_order')
            ->orderBy('t.id')
            ->get([
                't.*',
                DB::raw("ld.$nameCol as linked_department_name"),
                DB::raw("lpd_d.$nameCol as linked_project_department_name"),
                'lpd.department_status as linked_department_status',
            ])
            ->map(fn ($row) => $this->formatTaskRow((array) $row))
            ->all();
    }

    public function resolveTask(int $taskId): ?array
    {
        if (!$this->tableExists() || $taskId <= 0) {
            return null;
        }

        $nameCol = $this->projectDepartmentService->departmentNameColumn();
        $row = DB::table('tbl_project_department_tasks as t')
            ->leftJoin('tbl_departments as ld', 'ld.id', '=', 't.linked_department_id')
            ->leftJoin('tbl_project_departments as lpd', 'lpd.id', '=', 't.linked_project_department_id')
            ->leftJoin('tbl_departments as lpd_d', 'lpd_d.id', '=', 'lpd.department_id')
            ->where('t.id', $taskId)
            ->where('t.is_delete', 0)
            ->first([
                't.*',
                DB::raw("ld.$nameCol as linked_department_name"),
                DB::raw("lpd_d.$nameCol as linked_project_department_name"),
                'lpd.department_status as linked_department_status',
            ]);

        return $row ? $this->formatTaskRow((array) $row) : null;
    }

    public function resolveTaskForDepartment(int $taskId, int $projectDepartmentId): ?array
    {
        $task = $this->resolveTask($taskId);
        if (!$task || (int) ($task['project_department_id'] ?? 0) !== $projectDepartmentId) {
            return null;
        }

        return $task;
    }

    public function countTasksForProjectDepartment(int $projectDepartmentId): int
    {
        if (!$this->tableExists() || $projectDepartmentId <= 0) {
            return 0;
        }

        return (int) DB::table('tbl_project_department_tasks')
            ->where('project_department_id', $projectDepartmentId)
            ->where('is_delete', 0)
            ->whereNull('parent_task_id')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function saveTask(array $data): array
    {
        if (!$this->tableExists()) {
            return ['error' => 1, 'msg' => 'Task module is not available. Please run the database migration for tbl_project_department_tasks.'];
        }

        $projectDepartmentId = (int) ($data['project_department_id'] ?? 0);
        $parentPd = DB::table('tbl_project_departments')
            ->where('id', $projectDepartmentId)
            ->where('is_delete', 0)
            ->first();

        if (!$parentPd) {
            return ['error' => 1, 'msg' => 'Department not found'];
        }

        $projectId = (int) $parentPd->project_id;
        $taskId = !empty($data['id']) ? (int) $data['id'] : null;
        $oldTask = $taskId ? $this->resolveTask($taskId) : null;
        $validation = $this->validateTaskData($data, $projectId, $projectDepartmentId, $taskId);
        if ($validation !== '') {
            return ['error' => 1, 'msg' => $validation, 'validation' => $validation];
        }

        $taskName = trim((string) ($data['task_name'] ?? ''));
        $linkedDepartmentId = (int) ($data['linked_department_id'] ?? 0) ?: null;
        $kind = $linkedDepartmentId ? self::KIND_LINKED_DEPARTMENT : self::KIND_STANDARD;

        $linkedProjectDepartmentId = null;
        if ($linkedDepartmentId) {
            $linkedProjectDepartmentId = $this->ensureLinkedProjectDepartment(
                $projectId,
                $linkedDepartmentId,
                (int) $parentPd->department_id
            );
        }

        $payload = [
            'project_id' => $projectId,
            'project_department_id' => $projectDepartmentId,
            'parent_task_id' => !empty($data['parent_task_id']) ? (int) $data['parent_task_id'] : null,
            'sort_order' => $this->resolveNextSortOrder($projectDepartmentId, $taskId, $data['sort_order'] ?? null),
            'task_name' => $taskName,
            'task_kind' => $kind,
            'linked_department_id' => $linkedDepartmentId ?: null,
            'linked_project_department_id' => $linkedProjectDepartmentId,
            'planned_start_date' => $this->normalizeOptionalDate($data['planned_start_date'] ?? null),
            'planned_end_date' => $this->normalizeOptionalDate($data['planned_end_date'] ?? null),
            'task_status' => $this->normalizeStatus($data['task_status'] ?? self::STATUS_NOT_STARTED),
            'owner_name' => trim((string) ($data['owner_name'] ?? '')) ?: null,
            'remarks' => trim((string) ($data['remarks'] ?? '')) ?: null,
            'updated_by' => Auth::id(),
            'updated_on' => current_datetime(),
        ];

        if (!empty($data['owner_user_id'])) {
            $payload['owner_user_id'] = (int) $data['owner_user_id'];
        } elseif (array_key_exists('owner_user_id', $data) && ($data['owner_user_id'] === '' || $data['owner_user_id'] === null)) {
            $payload['owner_user_id'] = null;
        }

        if ($taskId) {
            DB::table('tbl_project_department_tasks')
                ->where('id', $taskId)
                ->where('is_delete', 0)
                ->update($payload);
            $savedId = $taskId;
        } else {
            $payload['created_by'] = Auth::id();
            $payload['created_on'] = current_datetime();
            $payload['is_delete'] = 0;
            $savedId = (int) DB::table('tbl_project_department_tasks')->insertGetId($payload);
        }

        $savedTask = $this->resolveTask($savedId);
        $this->auditTask($oldTask ? 'update' : 'create', $savedId, $oldTask, $savedTask);

        return [
            'error' => 0,
            'msg' => 'Task saved',
            'task' => $savedTask,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function updateTaskStatus(int $taskId, string $status): array
    {
        if (!$this->tableExists()) {
            return ['error' => 1, 'msg' => 'Task module is not available. Please run the database migration for tbl_project_department_tasks.'];
        }

        $task = $this->resolveTask($taskId);
        if (!$task) {
            return ['error' => 1, 'msg' => 'Task not found'];
        }

        $status = $this->normalizeStatus($status);
        if (!array_key_exists($status, $this->statusLabels())) {
            return ['error' => 1, 'msg' => 'Invalid task status'];
        }

        $oldTask = $task;

        DB::table('tbl_project_department_tasks')
            ->where('id', $taskId)
            ->where('is_delete', 0)
            ->update([
                'task_status' => $status,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);

        $savedTask = $this->resolveTask($taskId);
        $this->auditTask('update', $taskId, $oldTask, $savedTask);

        return [
            'error' => 0,
            'msg' => 'Status updated',
            'task' => $savedTask,
        ];
    }

    public function softDeleteTask(int $taskId): bool
    {
        if (!$this->tableExists() || $taskId <= 0) {
            return false;
        }

        $oldTask = $this->resolveTask($taskId);
        if (!$oldTask) {
            return false;
        }

        $deleted = (bool) DB::table('tbl_project_department_tasks')
            ->where('id', $taskId)
            ->where('is_delete', 0)
            ->update([
                'is_delete' => 1,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);

        if ($deleted) {
            $this->auditTask('delete', $taskId, $oldTask, null);
        }

        return $deleted;
    }

    /**
     * Master departments available for linking under a project department.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function getLinkableMasterDepartments(int $projectId, int $parentDepartmentId): array
    {
        $deptTable = $this->projectDepartmentService->departmentsTable();
        $nameCol = $this->projectDepartmentService->departmentNameColumn();

        return DB::table($deptTable)
            ->where('is_delete', 0)
            ->where('status', 1)
            ->where('id', '!=', $parentDepartmentId)
            ->orderBy($nameCol)
            ->get(['id', DB::raw("$nameCol as label")])
            ->map(fn ($row) => ['id' => (int) $row->id, 'label' => (string) $row->label])
            ->all();
    }

    /**
     * Ensure a project department row exists for a master department (reuse or create).
     */
    public function ensureLinkedProjectDepartment(int $projectId, int $linkedDepartmentId, int $parentDepartmentId): int
    {
        if ($linkedDepartmentId === $parentDepartmentId) {
            throw new \InvalidArgumentException('A department cannot be linked as a task under itself.');
        }

        $existing = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('department_id', $linkedDepartmentId)
            ->where('is_delete', 0)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $maxSort = (int) DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->max('sort_order');

        $userId = Auth::id();
        $now = current_datetime();

        return (int) DB::table('tbl_project_departments')->insertGetId([
            'project_id' => $projectId,
            'department_id' => $linkedDepartmentId,
            'sort_order' => $maxSort + 1,
            'department_status' => ProjectDepartmentService::STATUS_PENDING,
            'allow_parallel_next' => 0,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);
    }

    public function validateTaskData(array $data, int $projectId, int $projectDepartmentId, ?int $taskId = null): string
    {
        $taskName = trim((string) ($data['task_name'] ?? ''));
        if ($taskName === '') {
            return 'Please enter task name';
        }

        $linkedDepartmentId = (int) ($data['linked_department_id'] ?? 0) ?: null;

        $start = $this->normalizeOptionalDate($data['planned_start_date'] ?? null);
        $end = $this->normalizeOptionalDate($data['planned_end_date'] ?? null);
        if ($start && $end && $end < $start) {
            return 'Task end date cannot be earlier than start date';
        }

        $projectDateErr = $this->projectDepartmentService->validateDepartmentDatesAgainstProject($projectId, $start, $end);
        if ($projectDateErr !== '') {
            return strip_tags(str_replace(['<li>', '</li>'], ['', ' '], $projectDateErr));
        }

        $parentPd = DB::table('tbl_project_departments')->where('id', $projectDepartmentId)->where('is_delete', 0)->first();
        if ($linkedDepartmentId && $parentPd && $linkedDepartmentId === (int) $parentPd->department_id) {
            return 'A task cannot be linked to the same department';
        }

        $status = $this->normalizeStatus($data['task_status'] ?? self::STATUS_NOT_STARTED);
        if (!array_key_exists($status, $this->statusLabels())) {
            return 'Invalid task status';
        }

        return '';
    }

    public function syncLinkedTasksForDepartment(int $projectDepartmentId): void
    {
        if (!$this->tableExists() || $projectDepartmentId <= 0) {
            return;
        }

        $pd = DB::table('tbl_project_departments')->where('id', $projectDepartmentId)->where('is_delete', 0)->first();
        if (!$pd) {
            return;
        }

        $mappedStatus = $this->mapDepartmentStatusToTaskStatus((string) ($pd->department_status ?? ''));

        DB::table('tbl_project_department_tasks')
            ->where('linked_project_department_id', $projectDepartmentId)
            ->where('task_kind', self::KIND_LINKED_DEPARTMENT)
            ->where('is_delete', 0)
            ->update([
                'task_status' => $mappedStatus,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);
    }

    private function mapDepartmentStatusToTaskStatus(string $departmentStatus): string
    {
        return match ($departmentStatus) {
            ProjectDepartmentService::STATUS_COMPLETED => self::STATUS_COMPLETED,
            ProjectDepartmentService::STATUS_IN_PROGRESS, ProjectDepartmentService::STATUS_DELAY => self::STATUS_IN_PROGRESS,
            ProjectDepartmentService::STATUS_START => self::STATUS_NOT_STARTED,
            default => self::STATUS_NOT_STARTED,
        };
    }

    private function resolveNextSortOrder(int $projectDepartmentId, ?int $taskId, $requested): int
    {
        if ($requested !== null && $requested !== '') {
            return max(0, (int) $requested);
        }

        if ($taskId) {
            $current = DB::table('tbl_project_department_tasks')->where('id', $taskId)->value('sort_order');
            if ($current !== null) {
                return (int) $current;
            }
        }

        return (int) DB::table('tbl_project_department_tasks')
            ->where('project_department_id', $projectDepartmentId)
            ->where('is_delete', 0)
            ->max('sort_order') + 1;
    }

    private function masterDepartmentName(int $departmentId): string
    {
        $deptTable = $this->projectDepartmentService->departmentsTable();
        $nameCol = $this->projectDepartmentService->departmentNameColumn();

        return (string) (DB::table($deptTable)->where('id', $departmentId)->value($nameCol) ?? 'Linked department');
    }

    private function normalizeOptionalDate($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        $labels = $this->statusLabels();

        return array_key_exists($status, $labels) ? $status : self::STATUS_NOT_STARTED;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatTaskRow(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['project_id'] = (int) ($row['project_id'] ?? 0);
        $row['project_department_id'] = (int) ($row['project_department_id'] ?? 0);
        $row['task_kind'] = $row['task_kind'] ?? self::KIND_STANDARD;
        $row['task_status'] = $row['task_status'] ?? self::STATUS_NOT_STARTED;
        $row['status_label'] = $this->statusLabels()[$row['task_status']] ?? ucfirst($row['task_status']);
        $row['status_badge_html'] = $this->statusBadgeHtml($row['task_status']);
        $row['has_department_link'] = !empty($row['linked_department_id']);
        $row['is_linked_department'] = $row['has_department_link'];
        $row['display_name'] = trim((string) ($row['task_name'] ?? '')) ?: 'Task';

        if (!empty($row['planned_start_date'])) {
            $row['planned_start_date'] = date('Y-m-d', strtotime((string) $row['planned_start_date']));
        }
        if (!empty($row['planned_end_date'])) {
            $row['planned_end_date'] = date('Y-m-d', strtotime((string) $row['planned_end_date']));
        }

        $linkedPdId = (int) ($row['linked_project_department_id'] ?? 0);
        $row['linked_project_department_token'] = $linkedPdId > 0 ? Crypt::encrypt($linkedPdId) : '';

        return $row;
    }

    /**
     * @param  array<string, mixed>|null  $task
     * @return array<string, mixed>|null
     */
    private function auditSnapshot(?array $task): ?array
    {
        if (!$task) {
            return null;
        }

        return [
            'id' => $task['id'] ?? null,
            'project_id' => $task['project_id'] ?? null,
            'project_department_id' => $task['project_department_id'] ?? null,
            'task_name' => $task['task_name'] ?? null,
            'task_kind' => $task['task_kind'] ?? null,
            'linked_department_id' => $task['linked_department_id'] ?? null,
            'task_status' => $task['task_status'] ?? null,
            'planned_start_date' => $task['planned_start_date'] ?? null,
            'planned_end_date' => $task['planned_end_date'] ?? null,
        ];
    }

    private function auditTask(string $action, int $taskId, ?array $oldTask, ?array $newTask): void
    {
        $this->auditTrail->log(
            self::AUDIT_ENTITY,
            $taskId,
            $action,
            $this->auditSnapshot($oldTask),
            $this->auditSnapshot($newTask)
        );
    }
}
