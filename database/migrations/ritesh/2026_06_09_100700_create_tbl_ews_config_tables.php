<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 2 — EWS alert levels & escalation matrix. */
class CreateTblEwsConfigTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_ews_alert_levels')) {
            Schema::create('tbl_ews_alert_levels', function (Blueprint $table) {
                $table->id();
                $table->string('level_code', 20)->unique()->comment('green, amber, red, black');
                $table->string('level_label', 100);
                $table->string('description', 255)->nullable();
                $table->integer('sort_order')->default(0);
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);
            });
        }

        if (!Schema::hasTable('tbl_ews_escalation_matrix')) {
            Schema::create('tbl_ews_escalation_matrix', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('escalation_level')->unique()->comment('1-4');
                $table->string('escalation_role', 150)->comment('Project SPOC, Department Head, etc.');
                $table->string('trigger_severity', 30)->nullable()->comment('minor, moderate, critical, showstopper');
                $table->string('trigger_alert_level', 20)->nullable()->comment('green, amber, red, black');
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);
            });
        }

        if (!Schema::hasTable('tbl_ews_prediction_config')) {
            Schema::create('tbl_ews_prediction_config', function (Blueprint $table) {
                $table->id();
                $table->string('config_key', 100)->unique();
                $table->string('config_value', 255);
                $table->string('description', 255)->nullable();
                $table->integer('status')->default(1);
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_ews_prediction_config');
        Schema::dropIfExists('tbl_ews_escalation_matrix');
        Schema::dropIfExists('tbl_ews_alert_levels');
    }
}
