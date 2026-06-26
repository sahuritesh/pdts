<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Project department tasks — standard tasks and linked-department tasks. No DB foreign keys. */
class CreateTblProjectDepartmentTasks extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_project_department_tasks')) {
            Schema::create('tbl_project_department_tasks', function (Blueprint $table) {
                $table->id();
                $table->integer('project_id')->comment('tbl_projects.id');
                $table->integer('project_department_id')->comment('tbl_project_departments.id — parent dept instance');
                $table->integer('parent_task_id')->nullable()->comment('tbl_project_department_tasks.id — sub-task parent');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('task_name', 255);
                $table->string('task_kind', 30)->default('standard')->comment('standard, linked_department');
                $table->integer('linked_department_id')->nullable()->comment('tbl_departments.id when task_kind = linked_department');
                $table->integer('linked_project_department_id')->nullable()->comment('tbl_project_departments.id — drill-down target');
                $table->date('planned_start_date')->nullable();
                $table->date('planned_end_date')->nullable();
                $table->date('actual_start_date')->nullable();
                $table->date('actual_end_date')->nullable();
                $table->string('task_status', 30)->default('not_started')
                    ->comment('not_started, in_progress, completed, on_hold');
                $table->integer('owner_user_id')->nullable()->comment('tbl_user.id');
                $table->string('owner_name', 255)->nullable();
                $table->text('remarks')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('project_id');
                $table->index('project_department_id');
                $table->index('parent_task_id');
                $table->index('linked_department_id');
                $table->index('linked_project_department_id');
                $table->index('task_status');
                $table->index('sort_order');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_project_department_tasks');
    }
}
