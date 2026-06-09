<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Delay Register. delay_days = end_date - start_date (app-calculated). */
class CreateTblDelayRegisters extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_registers')) {
            Schema::create('tbl_delay_registers', function (Blueprint $table) {
                $table->id();
                $table->integer('project_id')->comment('tbl_projects.id');
                $table->string('delay_title', 255)->nullable();
                $table->text('delay_description')->nullable();
                $table->date('delay_start_date')->nullable();
                $table->date('delay_end_date')->nullable();
                $table->integer('delay_days')->default(0)->comment('End Date - Start Date; auto-updated in app');
                $table->integer('delay_category_id')->nullable()->comment('tbl_delay_categories.id');
                $table->integer('responsibility_user_id')->nullable()->comment('tbl_user.id');
                $table->string('responsibility_name', 255)->nullable();
                $table->string('severity', 30)->default('minor')->comment('minor, moderate, critical, showstopper');
                $table->tinyInteger('licensing_openings_affected')->default(0)->comment('1 = Showstopper regardless of days');
                $table->string('alert_level', 20)->default('green')->comment('green, amber, red, black');
                $table->unsignedTinyInteger('escalation_level')->nullable()->comment('1-4 per escalation matrix');
                $table->string('register_status', 30)->default('open')->comment('open, in_progress, closed');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('project_id');
                $table->index('delay_category_id');
                $table->index('severity');
                $table->index('alert_level');
                $table->index('escalation_level');
                $table->index('delay_start_date');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_registers');
    }
}
