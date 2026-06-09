<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Task Tracking (supports EWS prediction fields). */
class CreateTblRenovationTasks extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_tasks')) {
            Schema::create('tbl_renovation_tasks', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->string('task_category', 150)->nullable();
                $table->text('task_description')->nullable();
                $table->string('priority', 20)->default('medium')->comment('high, medium, low');
                $table->date('planned_start_date')->nullable();
                $table->date('planned_end_date')->nullable();
                $table->date('actual_start_date')->nullable();
                $table->date('actual_end_date')->nullable();
                $table->unsignedInteger('allocated_duration_days')->nullable();
                $table->unsignedInteger('elapsed_duration_days')->nullable();
                $table->decimal('task_completion_percent', 5, 2)->default(0);
                $table->decimal('consumed_duration_percent', 5, 2)->default(0);
                $table->string('task_status', 30)->default('pending')->comment('pending, in_progress, completed, delayed, blocked');
                $table->string('risk_level', 30)->nullable()->comment('low, medium, high, critical');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('priority');
                $table->index('task_status');
                $table->index('risk_level');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_tasks');
    }
}
