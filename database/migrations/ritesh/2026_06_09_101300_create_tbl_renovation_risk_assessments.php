<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Risk scoring snapshot (app calculates from delay/disruption/approval/material/dependency). */
class CreateTblRenovationRiskAssessments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_risk_assessments')) {
            Schema::create('tbl_renovation_risk_assessments', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->integer('renovation_task_id')->nullable()->comment('tbl_renovation_tasks.id');
                $table->integer('delay_days')->default(0);
                $table->unsignedTinyInteger('disruption_score')->nullable();
                $table->integer('approval_delay_days')->default(0);
                $table->integer('material_delay_days')->default(0);
                $table->integer('dependency_delay_days')->default(0);
                $table->string('risk_level', 30)->default('low')->comment('low, medium, high, critical');
                $table->text('assessment_notes')->nullable();
                $table->dateTime('assessed_on')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('renovation_task_id');
                $table->index('risk_level');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_risk_assessments');
    }
}
