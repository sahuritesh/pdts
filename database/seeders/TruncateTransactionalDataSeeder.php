<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears all transactional PDTS data (projects, delays, financials, attachments, renovation).
 * Master tables (departments, zones, types, root causes, roles, users, EWS config) are kept.
 */
class TruncateTransactionalDataSeeder extends Seeder
{
    /** Child-first order for safe deletes when FK checks are enabled. */
    private array $transactionalTables = [
        'tbl_delay_mitigations',
        'tbl_delay_attachments',
        'tbl_delay_financial_impacts',
        'tbl_delay_registers',
        'tbl_project_departments',
        'tbl_projects',
        'tbl_ews_potential_delay_alerts',
        'tbl_audit_trails',
        'tbl_notification_logs',
        'tbl_renovation_daily_delay_logs',
        'tbl_renovation_task_dependencies',
        'tbl_renovation_approvals',
        'tbl_renovation_procurements',
        'tbl_renovation_risk_assessments',
        'tbl_renovation_change_orders',
        'tbl_renovation_cost_tracking',
        'tbl_renovation_operational_impacts',
        'tbl_renovation_tasks',
        'tbl_renovation_projects',
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->transactionalTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)->truncate();
            $this->command->info("Truncated {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Transactional data cleared. Master data unchanged.');
    }
}
