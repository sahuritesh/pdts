<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProjectDepartments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_project_departments')) {
            Schema::create('tbl_project_departments', function (Blueprint $table) {
                $table->id();
                $table->integer('project_id')->comment('tbl_projects.id');
                $table->integer('department_id')->comment('tbl_departments.id');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->string('department_status', 30)->default('pending')
                    ->comment('pending, start, in_progress, delay, completed');
                $table->string('spoc_name', 255)->nullable();
                $table->integer('spoc_user_id')->nullable()->comment('tbl_user.id');
                $table->date('planned_start_date')->nullable();
                $table->date('planned_end_date')->nullable();
                $table->date('actual_start_date')->nullable();
                $table->date('actual_end_date')->nullable();
                $table->unsignedInteger('delay_days')->default(0);
                $table->text('remarks')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('project_id');
                $table->index('department_id');
                $table->index('sort_order');
                $table->index('department_status');
                $table->index('is_delete');
            });
        }

        if (Schema::hasTable('tbl_projects') && !Schema::hasColumn('tbl_projects', 'wizard_step')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->unsignedTinyInteger('wizard_step')->default(1)->after('project_status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_projects') && Schema::hasColumn('tbl_projects', 'wizard_step')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->dropColumn('wizard_step');
            });
        }

        Schema::dropIfExists('tbl_project_departments');
    }
}
