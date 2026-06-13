<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
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

    public function syncProjectDepartments(int $projectId, array $orderedDepartmentIds): void
    {
        $userId = Auth::id();
        $now = current_datetime();
        $existing = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->get()
            ->keyBy('department_id');

        $sort = 1;
        foreach ($orderedDepartmentIds as $departmentId) {
            $departmentId = (int) $departmentId;
            if ($departmentId <= 0) {
                continue;
            }

            if (isset($existing[$departmentId])) {
                DB::table('tbl_project_departments')
                    ->where('id', $existing[$departmentId]->id)
                    ->update([
                        'sort_order' => $sort,
                        'updated_by' => $userId,
                        'updated_on' => $now,
                    ]);
            } else {
                $status = $sort === 1 ? self::STATUS_START : self::STATUS_PENDING;
                DB::table('tbl_project_departments')->insert([
                    'project_id' => $projectId,
                    'department_id' => $departmentId,
                    'sort_order' => $sort,
                    'department_status' => $status,
                    'created_by' => $userId,
                    'created_on' => $now,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ]);
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

        $this->normalizeStatuses($projectId);
        $this->syncProjectRollupStatus($projectId);
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

        return ['error' => 0, 'msg' => 'Department marked completed'];
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
        if ($projectId <= 0 || $sortOrder <= 0) {
            return false;
        }

        $blocked = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->where('sort_order', '<', $sortOrder)
            ->where('department_status', '!=', self::STATUS_COMPLETED)
            ->exists();

        return !$blocked;
    }

    public function isAccordionExpandable(array $row, array $allRows): bool
    {
        $status = $row['department_status'] ?? self::STATUS_PENDING;
        if ($status === self::STATUS_PENDING) {
            return false;
        }

        return $this->canEditDepartment($row) || $status === self::STATUS_COMPLETED;
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

    private function normalizeStatuses(int $projectId): void
    {
        $rows = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->orderBy('sort_order')
            ->get();

        $foundActive = false;
        foreach ($rows as $row) {
            if ($row->department_status === self::STATUS_COMPLETED) {
                continue;
            }
            if (!$foundActive) {
                if ($row->department_status === self::STATUS_PENDING) {
                    DB::table('tbl_project_departments')->where('id', $row->id)->update([
                        'department_status' => self::STATUS_START,
                        'updated_on' => current_datetime(),
                    ]);
                }
                $foundActive = true;
            } else {
                if ($row->department_status !== self::STATUS_DELAY && $row->department_status !== self::STATUS_IN_PROGRESS) {
                    DB::table('tbl_project_departments')->where('id', $row->id)->update([
                        'department_status' => self::STATUS_PENDING,
                        'updated_on' => current_datetime(),
                    ]);
                }
            }
        }
    }

    private function activateNextDepartment(int $projectId): void
    {
        $next = DB::table('tbl_project_departments')
            ->where('project_id', $projectId)
            ->where('is_delete', 0)
            ->where('department_status', self::STATUS_PENDING)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            DB::table('tbl_project_departments')->where('id', $next->id)->update([
                'department_status' => self::STATUS_START,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);
        }
    }
}
