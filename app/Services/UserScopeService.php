<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserScopeService
{
    public function isScopedUser(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        if (permissionexists('spoc_department_access') === '1') {
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

    public function canViewAllProjects(): bool
    {
        return !$this->isScopedUser() || permissionexists('projects') === '1' && !$this->hasOnlySpocAccess();
    }

    public function hasOnlySpocAccess(): bool
    {
        return permissionexists('spoc_department_access') === '1'
            && permissionexists('projects') !== '1';
    }

    /** Scope project query for list/dashboard. */
    public function applyProjectScope(Builder $query, string $projectAlias = 'tp'): Builder
    {
        if (!$this->isScopedUser()) {
            return $query;
        }

        $deptIds = $this->getAssignedDepartmentIds();
        $userId = (int) Auth::id();

        return $query->where(function ($q) use ($deptIds, $userId, $projectAlias) {
            if (!empty($deptIds) && Schema::hasTable('tbl_project_departments')) {
                $q->whereExists(function ($sub) use ($deptIds, $projectAlias) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.project_id', $projectAlias . '.id')
                        ->where('pd.is_delete', 0)
                        ->whereIn('pd.department_id', $deptIds);
                });
            }

            if (Schema::hasTable('tbl_project_departments') && Schema::hasColumn('tbl_project_departments', 'spoc_user_id')) {
                $q->orWhereExists(function ($sub) use ($userId, $projectAlias) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd2')
                        ->whereColumn('pd2.project_id', $projectAlias . '.id')
                        ->where('pd2.is_delete', 0)
                        ->where('pd2.spoc_user_id', $userId);
                });
            }
        });
    }

    /** Scope project_department rows for SPOC task views. */
    public function applyProjectDepartmentScope(Builder $query, string $pdAlias = 'pd'): Builder
    {
        if (!$this->isScopedUser()) {
            return $query;
        }

        $deptIds = $this->getAssignedDepartmentIds();
        $userId = (int) Auth::id();

        return $query->where(function ($q) use ($deptIds, $userId, $pdAlias) {
            if (!empty($deptIds)) {
                $q->whereIn($pdAlias . '.department_id', $deptIds);
            }
            if (Schema::hasColumn('tbl_project_departments', 'spoc_user_id')) {
                $q->orWhere($pdAlias . '.spoc_user_id', $userId);
            }
        });
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
