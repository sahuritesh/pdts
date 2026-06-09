<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Approval Tracking. approval_pending_days auto-calculated in app. */
class CreateTblRenovationApprovals extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_approvals')) {
            Schema::create('tbl_renovation_approvals', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->integer('renovation_task_id')->nullable()->comment('tbl_renovation_tasks.id');
                $table->string('approval_type', 100)->nullable();
                $table->string('approval_status', 30)->default('pending')->comment('pending, approved, rejected');
                $table->date('submitted_date')->nullable();
                $table->date('approved_date')->nullable();
                $table->unsignedInteger('approval_pending_days')->default(0);
                $table->text('remarks')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('approval_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_approvals');
    }
}
