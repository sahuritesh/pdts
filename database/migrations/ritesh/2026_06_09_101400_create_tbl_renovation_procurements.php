<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Procurement Tracking. */
class CreateTblRenovationProcurements extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_procurements')) {
            Schema::create('tbl_renovation_procurements', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->integer('renovation_task_id')->nullable()->comment('tbl_renovation_tasks.id');
                $table->string('vendor_contractor', 255)->nullable();
                $table->string('procurement_status', 30)->default('pending')->comment('pending, ordered, in_transit, delivered, installed');
                $table->unsignedInteger('material_delay_days')->default(0);
                $table->text('notes')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('procurement_status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_procurements');
    }
}
