<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Project Master. No DB foreign keys. */
class CreateTblProjects extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_projects')) {
            Schema::create('tbl_projects', function (Blueprint $table) {
                $table->id();
                $table->string('project_code', 50)->unique();
                $table->string('project_name', 255);
                $table->text('project_scope')->nullable();
                $table->string('location', 255)->nullable();
                $table->string('hospital_name', 255)->nullable()->comment('For delay analytics by hospital');
                $table->string('contractor_name', 255)->nullable()->comment('For delay analytics by contractor');
                $table->string('zone_department', 255)->nullable();
                $table->integer('responsible_user_id')->nullable()->comment('tbl_user.id — Project SPOC');
                $table->string('responsibility_name', 255)->nullable();
                $table->date('planned_start_date')->nullable();
                $table->date('planned_completion_date')->nullable();
                $table->date('actual_completion_date')->nullable();
                $table->string('project_status', 30)->default('active')->comment('active, delayed, completed, on_hold');
                $table->decimal('total_delay_cost', 15, 2)->default(0)->comment('Roll-up: direct + opportunity');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('hospital_name');
                $table->index('contractor_name');
                $table->index('responsible_user_id');
                $table->index('project_status');
                $table->index('planned_completion_date');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_projects');
    }
}
