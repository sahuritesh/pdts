<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureTaskDashboardPermissionsOnRoles extends Migration
{
    private array $taskDashboardPermissions = [
        'dashboard_m1_task_kpis',
        'dashboard_m1_chart_task_status',
        'dashboard_m1_chart_top_tasks',
        'dashboard_m1_table_dept_open_tasks',
    ];

    public function up()
    {
        if (!Schema::hasTable('tbl_roles')) {
            return;
        }

        $roles = DB::table('tbl_roles')->where('is_delete', 0)->get(['id', 'permission_types']);

        foreach ($roles as $role) {
            $permissions = array_filter(array_map('trim', explode(',', (string) ($role->permission_types ?? ''))));
            if ($permissions === []) {
                continue;
            }

            $hasDashboard = in_array('dashboard_view', $permissions, true)
                || in_array('dashboard_m1_kpis', $permissions, true)
                || in_array('projects', $permissions, true);

            if (!$hasDashboard) {
                continue;
            }

            $changed = false;
            foreach ($this->taskDashboardPermissions as $permission) {
                if (!in_array($permission, $permissions, true)) {
                    $permissions[] = $permission;
                    $changed = true;
                }
            }

            if (!$changed) {
                continue;
            }

            DB::table('tbl_roles')->where('id', $role->id)->update([
                'permission_types' => implode(',', array_values(array_unique($permissions))),
                'updated_on' => now(),
            ]);
        }
    }

    public function down()
    {
        // Keep permissions — removing may break access unexpectedly.
    }
}
