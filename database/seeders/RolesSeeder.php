<?php

namespace Database\Seeders;

use App\Http\Controllers\RoleManagement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $allPermissions = array_merge(
            RoleManagement::allModulePermissionKeys(),
            RoleManagement::allDashboardPermissionKeys()
        );

        $roles = [
            [
                'role_name' => 'Super Admin',
                'role_description' => 'Full PDTS access including roles and settings',
                'permission_types' => implode(',', $allPermissions),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Admin',
                'role_description' => 'PDTS admin without role management',
                'permission_types' => implode(',', array_values(array_filter(
                    $allPermissions,
                    fn ($p) => $p !== 'roles'
                ))),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Manager',
                'role_description' => 'Manage projects, departments, and reports',
                'permission_types' => implode(',', array_merge(
                    ['dashboard_view', 'users', 'departments', 'locations', 'projects'],
                    RoleManagement::allDashboardPermissionKeys()
                )),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Viewer',
                'role_description' => 'Read-only dashboards and listings',
                'permission_types' => implode(',', array_merge(
                    ['dashboard_view', 'departments', 'projects'],
                    RoleManagement::allDashboardPermissionKeys()
                )),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Department SPOC',
                'role_description' => 'Department-scoped dashboard and task access',
                'permission_types' => implode(',', array_merge(
                    ['dashboard_view', 'spoc_department_access', 'spoc_tasks'],
                    [
                        'dashboard_m1_kpis',
                        'dashboard_m1_chart_category',
                        'dashboard_m1_chart_mitigation',
                        'dashboard_m1_table_critical',
                        'dashboard_m1_chart_zone',
                    ]
                )),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
        ];

        foreach ($roles as $role) {
            $exists = DB::table('tbl_roles')->where('role_name', $role['role_name'])->first();
            if (!$exists) {
                DB::table('tbl_roles')->insert($role);
                $this->command->info("Role '{$role['role_name']}' created.");
            } else {
                $existing = array_filter(explode(',', (string) $exists->permission_types));
                $incoming = array_filter(explode(',', (string) $role['permission_types']));
                $merged = implode(',', array_unique(array_merge($existing, $incoming)));
                DB::table('tbl_roles')->where('id', $exists->id)->update([
                    'permission_types' => $merged,
                    'updated_on' => $now,
                    'updated_by' => 1,
                ]);
                $this->command->info("Role '{$role['role_name']}' permissions synced.");
            }
        }
    }
}
