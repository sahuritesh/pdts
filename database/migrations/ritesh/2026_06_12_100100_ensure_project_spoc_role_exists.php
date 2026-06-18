<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureProjectSpocRoleExists extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_roles')) {
            return;
        }

        $now = now();
        $spocDashboardWidgets = [
            'dashboard_m1_kpis',
            'dashboard_m1_chart_category',
            'dashboard_m1_chart_mitigation',
            'dashboard_m1_table_critical',
            'dashboard_m1_chart_zone',
        ];
        $permissions = implode(',', array_merge(
            ['dashboard_view', 'my_projects', 'spoc_tasks'],
            $spocDashboardWidgets
        ));

        $roles = [
            'Department SPOC' => 'View related projects; manage department tasks',
            'Project SPOC' => 'Edit assigned projects; manage all departments on those projects',
        ];

        foreach ($roles as $roleName => $description) {
            $exists = DB::table('tbl_roles')->where('role_name', $roleName)->where('is_delete', 0)->first();
            if ($exists) {
                continue;
            }

            DB::table('tbl_roles')->insert([
                'role_name' => $roleName,
                'role_description' => $description,
                'permission_types' => $permissions,
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    public function down()
    {
        // Keep roles — other users may depend on them.
    }
}
