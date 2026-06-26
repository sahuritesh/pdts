<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Link delay register entries to project department tasks. No DB foreign keys. */
class AddProjectDepartmentTaskIdToDelayRegisters extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_delay_registers') && !Schema::hasColumn('tbl_delay_registers', 'project_department_task_id')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                $table->integer('project_department_task_id')->nullable()->after('project_department_id')
                    ->comment('tbl_project_department_tasks.id — optional impacted task');
                $table->index('project_department_task_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_delay_registers') && Schema::hasColumn('tbl_delay_registers', 'project_department_task_id')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                $table->dropIndex(['project_department_task_id']);
                $table->dropColumn('project_department_task_id');
            });
        }
    }
}
