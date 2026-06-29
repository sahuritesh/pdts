<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardPerformanceIndexes extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_delay_registers')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                if (!$this->indexExists('tbl_delay_registers', 'idx_dr_dashboard_filters')) {
                    $table->index(
                        ['is_delete', 'delay_start_date', 'project_id'],
                        'idx_dr_dashboard_filters'
                    );
                }
                if (!$this->indexExists('tbl_delay_registers', 'idx_dr_dashboard_dept_status')) {
                    $table->index(
                        ['is_delete', 'project_department_id', 'register_status'],
                        'idx_dr_dashboard_dept_status'
                    );
                }
            });
        }

        if (Schema::hasTable('tbl_project_departments')) {
            Schema::table('tbl_project_departments', function (Blueprint $table) {
                if (!$this->indexExists('tbl_project_departments', 'idx_pd_dashboard_filters')) {
                    $table->index(
                        ['is_delete', 'project_id', 'department_status'],
                        'idx_pd_dashboard_filters'
                    );
                }
            });
        }

        if (Schema::hasTable('tbl_projects')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                if (!$this->indexExists('tbl_projects', 'idx_tp_dashboard_zone_status')) {
                    $table->index(
                        ['is_delete', 'zone_id', 'project_status'],
                        'idx_tp_dashboard_zone_status'
                    );
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_delay_registers')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                if ($this->indexExists('tbl_delay_registers', 'idx_dr_dashboard_filters')) {
                    $table->dropIndex('idx_dr_dashboard_filters');
                }
                if ($this->indexExists('tbl_delay_registers', 'idx_dr_dashboard_dept_status')) {
                    $table->dropIndex('idx_dr_dashboard_dept_status');
                }
            });
        }

        if (Schema::hasTable('tbl_project_departments')) {
            Schema::table('tbl_project_departments', function (Blueprint $table) {
                if ($this->indexExists('tbl_project_departments', 'idx_pd_dashboard_filters')) {
                    $table->dropIndex('idx_pd_dashboard_filters');
                }
            });
        }

        if (Schema::hasTable('tbl_projects')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                if ($this->indexExists('tbl_projects', 'idx_tp_dashboard_zone_status')) {
                    $table->dropIndex('idx_tp_dashboard_zone_status');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->total ?? 0) > 0;
    }
}
