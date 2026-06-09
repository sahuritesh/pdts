<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Renovation Project Master. */
class CreateTblRenovationProjects extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_projects')) {
            Schema::create('tbl_renovation_projects', function (Blueprint $table) {
                $table->id();
                $table->string('project_code', 50)->unique()->comment('Project ID');
                $table->string('project_name', 255);
                $table->text('project_scope')->nullable();
                $table->string('location', 255)->nullable();
                $table->string('zone_department_impacted', 255)->nullable();
                $table->string('renovation_type', 150)->nullable();
                $table->string('project_status', 30)->default('active');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('project_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_projects');
    }
}
