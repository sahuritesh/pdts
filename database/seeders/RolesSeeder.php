<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $pdtsPermissions = [
            'delay_categories', 'delay_categories_list',
            'projects', 'projects_list', 'projects_create',
            'delay_registers', 'delay_registers_list', 'delay_registers_create',
            'mitigations', 'mitigations_list',
            'financial_impacts', 'financial_impacts_list',
            'delay_attachments',
            'ews_alerts', 'ews_config',
            'renovation_projects', 'renovation_projects_list', 'renovation_projects_create',
            'renovation_tasks', 'renovation_tasks_list',
            'renovation_daily_logs', 'renovation_daily_logs_list',
            'renovation_procurements', 'renovation_approvals', 'renovation_change_orders',
            'renovation_costs', 'renovation_risks',
            'executive_dashboard', 'delay_analytics', 'renovation_dashboard',
            'audit_trail',
        ];

        $roles = [
            [
                'role_name' => 'Super Admin',
                'role_description' => 'Full PDTS access including roles and settings',
                'permission_types' => implode(',', array_merge([
                    'dashboard_view', 'roles', 'users_creation', 'users_list',
                    'email_templates', 'settings', 'smtp_settings', 'razorpay_settings',
                    'send_push_notification', 'push_notifications_listing',
                ], $pdtsPermissions)),
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
                'permission_types' => implode(',', array_merge([
                    'dashboard_view', 'users_creation', 'users_list',
                    'email_templates', 'settings', 'smtp_settings', 'razorpay_settings',
                    'send_push_notification', 'push_notifications_listing',
                ], $pdtsPermissions)),
                'status' => 1,
                'created_by' => 1,
                'created_on' => $now,
                'updated_by' => 1,
                'updated_on' => $now,
                'is_delete' => 0,
            ],
            [
                'role_name' => 'Manager',
                'role_description' => 'Manage delays, renovation projects, and reports',
                'permission_types' => implode(',', [
                    'dashboard_view', 'users_list',
                    'delay_categories', 'delay_categories_list',
                    'projects', 'projects_list', 'projects_create',
                    'delay_registers', 'delay_registers_list', 'delay_registers_create',
                    'mitigations', 'mitigations_list',
                    'financial_impacts', 'financial_impacts_list',
                    'delay_attachments',
                    'ews_alerts',
                    'renovation_projects', 'renovation_projects_list', 'renovation_projects_create',
                    'renovation_tasks', 'renovation_tasks_list',
                    'renovation_daily_logs', 'renovation_daily_logs_list',
                    'renovation_procurements', 'renovation_approvals', 'renovation_change_orders',
                    'renovation_costs', 'renovation_risks',
                    'executive_dashboard', 'delay_analytics', 'renovation_dashboard',
                    'send_push_notification', 'push_notifications_listing',
                ]),
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
                'permission_types' => implode(',', [
                    'dashboard_view',
                    'projects_list', 'delay_registers_list', 'mitigations_list',
                    'delay_categories_list',
                    'financial_impacts_list',
                    'renovation_projects_list', 'renovation_tasks_list',
                    'renovation_daily_logs_list',
                    'executive_dashboard', 'delay_analytics', 'renovation_dashboard',
                ]),
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
