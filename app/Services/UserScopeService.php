<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserScopeService
{
    public function hasFullProjectsPermission(): bool
    {
        return permissionexists('projects') === '1';
    }

    public function hasMyProjectsAccess(): bool
    {
        if (permissionexists('my_projects') === '1') {
            return true;
        }

        // Legacy keys (roles saved before simplification).
        return permissionexists('spoc_project_access') === '1'
            || permissionexists('spoc_department_access') === '1';
    }

    public function hasMyDepartmentTasksAccess(): bool
    {
        return permissionexists('spoc_tasks') === '1';
    }

    public function shouldUseMyProjectsListing(): bool
    {
        return $this->hasMyProjectsAccess() && !$this->hasFullProjectsPermission();
    }

    public function isScopedUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if ($this->hasFullProjectsPermission()) {
            return false;
        }

        if ($this->hasMyProjectsAccess() || $this->hasMyDepartmentTasksAccess()) {
            return true;
        }

        return $this->getAssignedDepartmentIds() !== [];
    }

    /** @return int[] */
    public function getAssignedDepartmentIds(): array
    {
        if (!Auth::check() || !Schema::hasTable('tbl_user_departments')) {
            return [];
        }

        $userId = (int) Auth::id();

        return DB::table('tbl_user_departments')
            ->where('user_id', $userId)
            ->where('is_delete', 0)
            ->where('status', 1)
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function hasAssignedProjectsAsResponsible(): bool
    {
        if (!Auth::check() || !Schema::hasColumn('tbl_projects', 'responsible_user_id')) {
            return false;
        }

        return DB::table('tbl_projects')
            ->where('responsible_user_id', (int) Auth::id())
            ->where('is_delete', 0)
            ->exists();
    }

    public function canEditProject(int $projectId): bool
    {
        if ($this->hasFullProjectsPermission()) {
            return true;
        }

        if (!Schema::hasColumn('tbl_projects', 'responsible_user_id')) {
            return false;
        }

        return DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->where('responsible_user_id', (int) Auth::id())
            ->exists();
    }

    public function canAccessProject(int $projectId): bool
    {
        if (!$this->isScopedUser()) {
            return true;
        }

        $query = DB::table('tbl_projects as tp')
            ->where('tp.id', $projectId)
            ->where('tp.is_delete', 0);

        return $this->applyProjectScope($query, 'tp')->exists();
    }

    /** Scope project query for list/dashboard. */
    public function applyProjectScope(Builder $query, string $projectAlias = 'tp'): Builder
    {
        if (!$this->isScopedUser()) {
            return $query;
        }

        $deptIds = $this->getAssignedDepartmentIds();
        $userId = (int) Auth::id();
        $hasResponsibleColumn = Schema::hasColumn('tbl_projects', 'responsible_user_id');
        $hasSpocColumn = Schema::hasTable('tbl_project_departments')
            && Schema::hasColumn('tbl_project_departments', 'spoc_user_id');

        return $query->where(function ($q) use ($deptIds, $userId, $projectAlias, $hasResponsibleColumn, $hasSpocColumn) {
            $applied = false;

            if ($hasResponsibleColumn) {
                $q->where($projectAlias . '.responsible_user_id', $userId);
                $applied = true;
            }

            if (!empty($deptIds) && Schema::hasTable('tbl_project_departments')) {
                $deptExists = function ($sub) use ($deptIds, $projectAlias) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.project_id', $projectAlias . '.id')
                        ->where('pd.is_delete', 0)
                        ->whereIn('pd.department_id', $deptIds);
                };

                if ($applied) {
                    $q->orWhereExists($deptExists);
                } else {
                    $q->whereExists($deptExists);
                    $applied = true;
                }
            }

            if ($hasSpocColumn) {
                $spocExists = function ($sub) use ($userId, $projectAlias) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd2')
                        ->whereColumn('pd2.project_id', $projectAlias . '.id')
                        ->where('pd2.is_delete', 0)
                        ->where('pd2.spoc_user_id', $userId);
                };

                if ($applied) {
                    $q->orWhereExists($spocExists);
                } else {
                    $q->whereExists($spocExists);
                    $applied = true;
                }
            }

            if (!$applied) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    /**
     * My Projects listing — only projects where the user is the Project SPOC
     * (responsible_user_id on tbl_projects). Department-level assignments
     * belong in My Department Tasks, not this list.
     */
    public function applyMyProjectsScope(Builder $query, string $projectAlias = 'tp'): Builder
    {
        $userId = (int) Auth::id();

        if (!Schema::hasColumn('tbl_projects', 'responsible_user_id')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($projectAlias . '.responsible_user_id', $userId);
    }

    /** Scope project_department rows for SPOC task views. */
    public function applyProjectDepartmentScope(Builder $query, string $pdAlias = ''): Builder
    {
        if (!$this->isScopedUser()) {
            return $query;
        }

        $deptIds = $this->getAssignedDepartmentIds();
        $userId = (int) Auth::id();
        $hasResponsibleColumn = Schema::hasColumn('tbl_projects', 'responsible_user_id');
        $hasSpocColumn = Schema::hasTable('tbl_project_departments')
            && Schema::hasColumn('tbl_project_departments', 'spoc_user_id');

        return $query->where(function ($q) use ($deptIds, $userId, $pdAlias, $hasResponsibleColumn, $hasSpocColumn) {
            $applied = false;

            if ($hasResponsibleColumn) {
                $q->whereExists(function ($sub) use ($userId, $pdAlias) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_projects as tp_scope')
                        ->whereColumn('tp_scope.id', $this->qualify($pdAlias, 'project_id'))
                        ->where('tp_scope.is_delete', 0)
                        ->where('tp_scope.responsible_user_id', $userId);
                });
                $applied = true;
            }

            if ($hasSpocColumn) {
                $deptScope = function ($subQ) use ($deptIds, $userId, $pdAlias, $hasSpocColumn) {
                    $subQ->where($this->qualify($pdAlias, 'spoc_user_id'), $userId);

                    if (!empty($deptIds)) {
                        $subQ->orWhere(function ($inner) use ($deptIds, $pdAlias, $hasSpocColumn) {
                            $inner->whereIn($this->qualify($pdAlias, 'department_id'), $deptIds);
                            if ($hasSpocColumn) {
                                $inner->whereNull($this->qualify($pdAlias, 'spoc_user_id'));
                            }
                        });
                    }
                };

                if ($applied) {
                    $q->orWhere(function ($subQ) use ($deptScope) {
                        $deptScope($subQ);
                    });
                } else {
                    $deptScope($q);
                    $applied = true;
                }
            } elseif (!empty($deptIds)) {
                $deptOnly = function ($subQ) use ($deptIds, $pdAlias) {
                    $subQ->whereIn($this->qualify($pdAlias, 'department_id'), $deptIds);
                };

                if ($applied) {
                    $q->orWhere(function ($subQ) use ($deptOnly) {
                        $deptOnly($subQ);
                    });
                } else {
                    $deptOnly($q);
                    $applied = true;
                }
            }

            if (!$applied) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    public function canAccessProjectDepartment(int $projectDepartmentId): bool
    {
        if (!$this->isScopedUser()) {
            return true;
        }

        if (!Schema::hasTable('tbl_project_departments')) {
            return false;
        }

        $userId = (int) Auth::id();
        $deptIds = $this->getAssignedDepartmentIds();

        $row = DB::table('tbl_project_departments as pd')
            ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
            ->where('pd.id', $projectDepartmentId)
            ->where('pd.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->first(['pd.department_id', 'pd.spoc_user_id', 'tp.responsible_user_id']);

        if (!$row) {
            return false;
        }

        if (Schema::hasColumn('tbl_projects', 'responsible_user_id')
            && (int) ($row->responsible_user_id ?? 0) === $userId) {
            return true;
        }

        if (Schema::hasColumn('tbl_project_departments', 'spoc_user_id') && (int) ($row->spoc_user_id ?? 0) === $userId) {
            return true;
        }

        if (!empty($deptIds)
            && in_array((int) $row->department_id, $deptIds, true)
            && empty($row->spoc_user_id)) {
            return true;
        }

        return false;
    }

    private function qualify(string $alias, string $column): string
    {
        return $alias === '' ? $column : "{$alias}.{$column}";
    }

    public function syncUserDepartments(int $userId, array $departmentIds): void
    {
        if (!Schema::hasTable('tbl_user_departments')) {
            return;
        }

        $departmentIds = array_values(array_filter(array_map('intval', $departmentIds)));
        $now = current_datetime();
        $actorId = Auth::id();

        DB::table('tbl_user_departments')
            ->where('user_id', $userId)
            ->where('is_delete', 0)
            ->whereNotIn('department_id', $departmentIds ?: [0])
            ->update([
                'is_delete' => 1,
                'updated_by' => $actorId,
                'updated_on' => $now,
            ]);

        foreach ($departmentIds as $deptId) {
            $existing = DB::table('tbl_user_departments')
                ->where('user_id', $userId)
                ->where('department_id', $deptId)
                ->first();

            if ($existing) {
                DB::table('tbl_user_departments')->where('id', $existing->id)->update([
                    'is_delete' => 0,
                    'status' => 1,
                    'updated_by' => $actorId,
                    'updated_on' => $now,
                ]);
            } else {
                DB::table('tbl_user_departments')->insert([
                    'user_id' => $userId,
                    'department_id' => $deptId,
                    'is_primary' => 1,
                    'status' => 1,
                    'created_by' => $actorId,
                    'created_on' => $now,
                    'updated_by' => $actorId,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ]);
            }
        }
    }

    public function getUserDepartmentIds(int $userId): array
    {
        if (!Schema::hasTable('tbl_user_departments')) {
            return [];
        }

        return DB::table('tbl_user_departments')
            ->where('user_id', $userId)
            ->where('is_delete', 0)
            ->pluck('department_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
