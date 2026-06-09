<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Dependency Management. */
class CreateTblRenovationTaskDependencies extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_task_dependencies')) {
            Schema::create('tbl_renovation_task_dependencies', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_task_id')->comment('tbl_renovation_tasks.id — dependent task');
                $table->integer('dependency_task_id')->comment('tbl_renovation_tasks.id — prerequisite');
                $table->string('dependency_status', 30)->default('pending')->comment('pending, in_progress, completed, blocked');
                $table->text('notes')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_task_id');
                $table->index('dependency_task_id');
                $table->index('dependency_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_task_dependencies');
    }
}
