<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Cost Tracking. cost_overrun_percent calculated in app. */
class CreateTblRenovationCostTracking extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_cost_tracking')) {
            Schema::create('tbl_renovation_cost_tracking', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->decimal('budgeted_cost', 15, 2)->default(0);
                $table->decimal('actual_cost', 15, 2)->default(0);
                $table->decimal('cost_overrun_percent', 8, 2)->default(0);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('renovation_project_id');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_renovation_cost_tracking');
    }
}
