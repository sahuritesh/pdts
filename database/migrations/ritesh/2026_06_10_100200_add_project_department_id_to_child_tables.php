<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectDepartmentIdToChildTables extends Migration
{
    public function up()
    {
        foreach (['tbl_delay_registers', 'tbl_delay_financial_impacts', 'tbl_delay_attachments'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'project_department_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->integer('project_department_id')->nullable()->after('project_id')
                        ->comment('tbl_project_departments.id');
                    $table->index('project_department_id');
                });
            }
        }

        foreach (['tbl_delay_financial_impacts', 'tbl_delay_attachments'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'delay_register_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->integer('delay_register_id')->nullable()->change();
                });
            }
        }
    }

    public function down()
    {
        foreach (['tbl_delay_registers', 'tbl_delay_financial_impacts', 'tbl_delay_attachments'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'project_department_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex([$tableName . '_project_department_id_index']);
                    $table->dropColumn('project_department_id');
                });
            }
        }
    }
}
