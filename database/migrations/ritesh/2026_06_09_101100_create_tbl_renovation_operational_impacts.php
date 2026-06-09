<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Operational Impact Assessment (per renovation project). */
class CreateTblRenovationOperationalImpacts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_operational_impacts')) {
            Schema::create('tbl_renovation_operational_impacts', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->string('shutdown_required', 10)->default('no')->comment('yes, no');
                $table->unsignedTinyInteger('patient_service_disruption_score')->nullable()->comment('1-10');
                $table->string('temporary_relocation_needed', 10)->default('no')->comment('yes, no');
                $table->string('infection_control_clearance', 20)->default('pending')->comment('pending, approved, rejected');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id', 'reno_op_impacts_project_idx');
                $table->index('infection_control_clearance', 'reno_op_impacts_ic_clearance_idx');
                $table->index('is_delete', 'reno_op_impacts_is_delete_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_operational_impacts');
    }
}
