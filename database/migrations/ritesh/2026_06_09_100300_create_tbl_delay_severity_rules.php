<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Severity rules (auto-calculation config). No DB foreign keys. */
class CreateTblDelaySeverityRules extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_severity_rules')) {
            Schema::create('tbl_delay_severity_rules', function (Blueprint $table) {
                $table->id();
                $table->string('severity_code', 30)->unique()->comment('minor, moderate, critical, showstopper');
                $table->string('severity_label', 100);
                $table->unsignedInteger('min_delay_days')->nullable();
                $table->unsignedInteger('max_delay_days')->nullable();
                $table->tinyInteger('requires_licensing_flag')->default(0)->comment('1 for showstopper rule');
                $table->unsignedTinyInteger('default_escalation_level')->nullable();
                $table->integer('sort_order')->default(0);
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_severity_rules');
    }
}
