<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Drops accidental DB foreign keys on PDTS tables. Relationships are app-managed. */
class EnsureNoForeignKeysOnPdtsTables extends Migration
{
    private array $tables = [
        'tbl_delay_categories',
        'tbl_projects',
        'tbl_delay_registers',
        'tbl_delay_severity_rules',
        'tbl_delay_mitigations',
        'tbl_delay_financial_impacts',
        'tbl_delay_attachments',
        'tbl_ews_alert_levels',
        'tbl_ews_escalation_matrix',
        'tbl_ews_prediction_config',
        'tbl_ews_potential_delay_alerts',
        'tbl_renovation_projects',
        'tbl_renovation_tasks',
        'tbl_renovation_operational_impacts',
        'tbl_renovation_task_dependencies',
        'tbl_renovation_risk_assessments',
        'tbl_renovation_procurements',
        'tbl_renovation_approvals',
        'tbl_renovation_change_orders',
        'tbl_renovation_cost_tracking',
        'tbl_renovation_daily_delay_logs',
        'tbl_audit_trails',
    ];

    public function up()
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $database = DB::getDatabaseName();

        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
                [$database, $tableName, 'FOREIGN KEY']
            );

            foreach ($constraints as $row) {
                $name = $row->CONSTRAINT_NAME;
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                    str_replace('`', '``', $tableName),
                    str_replace('`', '``', $name)
                ));
            }
        }
    }

    public function down()
    {
        // Intentionally empty.
    }
}
