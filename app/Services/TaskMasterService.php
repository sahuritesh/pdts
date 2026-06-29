<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaskMasterService
{
    public const AUDIT_ENTITY = 'task_master';

    public function __construct(
        protected AuditTrailService $auditTrail
    ) {
    }

    public function tableExists(): bool
    {
        return Schema::hasTable('tbl_tasks');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchTasks(string $term, int $limit = 20, ?int $includeId = null): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $term = trim($term);

        $query = DB::table('tbl_tasks')
            ->where('is_delete', 0)
            ->where('status', 1);

        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('task_name', 'like', $like)
                    ->orWhere('task_code', 'like', $like);
            });
        }

        $collection = $query
            ->orderBy('task_name')
            ->limit($limit)
            ->get(['id', 'task_name', 'task_code', 'status']);

        if ($includeId && $includeId > 0 && !$collection->contains('id', $includeId)) {
            $included = $this->resolveTask($includeId);
            if ($included) {
                $collection->prepend((object) $included);
            }
        }

        return $collection
            ->unique('id')
            ->values()
            ->map(fn ($row) => $this->formatRow((array) (array) $row))
            ->all();
    }

    public function resolveTask(int $taskId, bool $activeOnly = false): ?array
    {
        if (!$this->tableExists() || $taskId <= 0) {
            return null;
        }

        $query = DB::table('tbl_tasks')
            ->where('id', $taskId)
            ->where('is_delete', 0);

        if ($activeOnly) {
            $query->where('status', 1);
        }

        $row = $query->first();

        return $row ? $this->formatRow((array) $row) : null;
    }

    /**
     * @return array{error: int, msg?: string, task?: array<string, mixed>}
     */
    public function quickCreate(string $taskName, string $taskCode = ''): array
    {
        if (!$this->tableExists()) {
            return ['error' => 1, 'msg' => 'Task master is not available. Please run the database migration for tbl_tasks.'];
        }

        $taskName = trim($taskName);
        if ($taskName === '') {
            return ['error' => 1, 'msg' => 'Please enter task name'];
        }

        $existing = $this->findByName($taskName);
        if ($existing) {
            return ['error' => 0, 'msg' => 'Task already exists', 'task' => $existing];
        }

        $userId = Auth::id();
        $now = current_datetime();
        $payload = [
            'task_name' => $taskName,
            'task_code' => trim($taskCode) !== '' ? trim($taskCode) : null,
            'description' => null,
            'status' => 1,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ];

        $newId = (int) DB::table('tbl_tasks')->insertGetId($payload);
        $task = $this->resolveTask($newId);
        $this->auditTrail->log(self::AUDIT_ENTITY, $newId, 'create', null, $this->auditSnapshot($task));

        return ['error' => 0, 'msg' => 'Task added to catalog', 'task' => $task];
    }

    /**
     * @return array{error: int, msg?: string, task?: array<string, mixed>}
     */
    public function saveTask(array $data): array
    {
        if (!$this->tableExists()) {
            return ['error' => 1, 'msg' => 'Task master is not available. Please run the database migration for tbl_tasks.'];
        }

        $taskId = !empty($data['task_id']) ? (int) $data['task_id'] : null;
        $taskName = trim((string) ($data['task_name'] ?? ''));
        if ($taskName === '') {
            return ['error' => 1, 'msg' => 'Please enter task name'];
        }

        $duplicate = $this->findByName($taskName, $taskId);
        if ($duplicate) {
            return ['error' => 1, 'msg' => 'Task name already exists'];
        }

        $userId = Auth::id();
        $now = current_datetime();
        $payload = [
            'task_name' => $taskName,
            'task_code' => trim((string) ($data['task_code'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'status' => isset($data['status']) ? (int) $data['status'] : 1,
            'updated_by' => $userId,
            'updated_on' => $now,
        ];

        if ($taskId) {
            $old = $this->resolveTask($taskId);
            if (!$old) {
                return ['error' => 1, 'msg' => 'Task not found'];
            }

            DB::table('tbl_tasks')->where('id', $taskId)->where('is_delete', 0)->update($payload);
            $saved = $this->resolveTask($taskId);
            $this->auditTrail->log(self::AUDIT_ENTITY, $taskId, 'update', $this->auditSnapshot($old), $this->auditSnapshot($saved));

            return ['error' => 0, 'msg' => 'Task updated', 'task' => $saved];
        }

        $payload['created_by'] = $userId;
        $payload['created_on'] = $now;
        $payload['is_delete'] = 0;
        $newId = (int) DB::table('tbl_tasks')->insertGetId($payload);
        $saved = $this->resolveTask($newId);
        $this->auditTrail->log(self::AUDIT_ENTITY, $newId, 'create', null, $this->auditSnapshot($saved));

        return ['error' => 0, 'msg' => 'Task added', 'task' => $saved];
    }

    public function findByName(string $taskName, ?int $excludeId = null): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $normalized = mb_strtolower(trim($taskName));
        if ($normalized === '') {
            return null;
        }

        $query = DB::table('tbl_tasks')
            ->where('is_delete', 0)
            ->whereRaw('LOWER(TRIM(task_name)) = ?', [$normalized]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $row = $query->first();

        return $row ? $this->formatRow((array) $row) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['task_name'] = trim((string) ($row['task_name'] ?? ''));
        $row['task_code'] = trim((string) ($row['task_code'] ?? ''));
        $row['status'] = (int) ($row['status'] ?? 1);

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
            'task_name' => $task['task_name'] ?? null,
            'task_code' => $task['task_code'] ?? null,
            'status' => $task['status'] ?? null,
        ];
    }
}
