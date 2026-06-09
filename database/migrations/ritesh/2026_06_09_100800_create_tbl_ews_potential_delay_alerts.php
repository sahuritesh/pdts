<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 2 — Generated potential delay alerts from prediction engine. */
class CreateTblEwsPotentialDelayAlerts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_ews_potential_delay_alerts')) {
            Schema::create('tbl_ews_potential_delay_alerts', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_task_id')->nullable()->comment('tbl_renovation_tasks.id');
                $table->integer('project_id')->nullable()->comment('tbl_projects.id');
                $table->decimal('task_completion_percent', 5, 2)->nullable();
                $table->decimal('consumed_duration_percent', 5, 2)->nullable();
                $table->unsignedInteger('allocated_duration_days')->nullable();
                $table->unsignedInteger('elapsed_duration_days')->nullable();
                $table->string('alert_level', 20)->default('amber')->comment('green, amber, red, black');
                $table->string('alert_status', 30)->default('open')->comment('open, acknowledged, closed');
                $table->text('alert_message')->nullable();
                $table->dateTime('alert_generated_on')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_task_id');
                $table->index('project_id');
                $table->index('alert_level');
                $table->index('alert_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_ews_potential_delay_alerts');
    }
}
