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

        $spocDashboardWidgets = [
            'dashboard_m1_kpis',
            'dashboard_m1_chart_category',
            'dashboard_m1_chart_mitigation',
            'dashboard_m1_table_critical',
            'dashboard_m1_chart_zone',
        ];

        $roles = [
            [
                'role_name' => 'Super Admin',
                'role_description' => 'Full PDTS access including roles and settings',
                'permission_types' => implode(',', $allPermissions),
                'replace_permissions' => false,
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
                'replace_permissions' => false,
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
                'replace_permissions' => false,
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
                'replace_permissions' => false,
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Department SPOC',
                'role_description' => 'Manage assigned projects and department tasks (access is by project/department assignment)',
                'permission_types' => implode(',', array_merge(
                    ['dashboard_view', 'my_projects', 'spoc_tasks'],
                    $spocDashboardWidgets
                )),
                'replace_permissions' => true,
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
        ];

        foreach ($roles as $role) {
            $replace = (bool) ($role['replace_permissions'] ?? false);
            unset($role['replace_permissions']);

            $exists = DB::table('tbl_roles')->where('role_name', $role['role_name'])->first();
            if (!$exists) {
                DB::table('tbl_roles')->insert($role);
                $this->command->info("Role '{$role['role_name']}' created.");
            } else {
                $permissionTypes = $role['permission_types'];
                if (!$replace) {
                    $existing = array_filter(explode(',', (string) $exists->permission_types));
                    $incoming = array_filter(explode(',', (string) $permissionTypes));
                    $permissionTypes = implode(',', array_unique(array_merge($existing, $incoming)));
                }

                DB::table('tbl_roles')->where('id', $exists->id)->update([
                    'role_description' => $role['role_description'],
                    'permission_types' => $permissionTypes,
                    'updated_on' => $now,
                    'updated_by' => 1,
                ]);
                $this->command->info("Role '{$role['role_name']}' permissions synced.");
            }
        }
    }
}
