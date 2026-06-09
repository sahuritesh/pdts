<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Mitigation Tracking. */
class CreateTblDelayMitigations extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_mitigations')) {
            Schema::create('tbl_delay_mitigations', function (Blueprint $table) {
                $table->id();
                $table->integer('delay_register_id')->comment('tbl_delay_registers.id');
                $table->text('mitigation_action')->nullable();
                $table->integer('owner_user_id')->nullable()->comment('tbl_user.id');
                $table->string('owner_name', 255)->nullable();
                $table->date('target_resolution_date')->nullable();
                $table->string('current_status', 30)->default('open')->comment('open, in_progress, escalated, closed');
                $table->text('resolution_remarks')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('delay_register_id');
                $table->index('owner_user_id');
                $table->index('current_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_mitigations');
    }
}
