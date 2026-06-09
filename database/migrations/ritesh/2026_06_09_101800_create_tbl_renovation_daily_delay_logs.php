<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Daily Delay Log (complete delay history). */
class CreateTblRenovationDailyDelayLogs extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_daily_delay_logs')) {
            Schema::create('tbl_renovation_daily_delay_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->integer('renovation_task_id')->nullable()->comment('tbl_renovation_tasks.id');
                $table->date('log_date');
                $table->text('delay_reason')->nullable();
                $table->integer('entered_by')->nullable()->comment('tbl_user.id');
                $table->text('corrective_action')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('renovation_task_id');
                $table->index('log_date');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_daily_delay_logs');
    }
}
