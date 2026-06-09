<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Financial Impact. Total = direct + opportunity (app-calculated). */
class CreateTblDelayFinancialImpacts extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_financial_impacts')) {
            Schema::create('tbl_delay_financial_impacts', function (Blueprint $table) {
                $table->id();
                $table->integer('delay_register_id')->comment('tbl_delay_registers.id');
                $table->integer('project_id')->nullable()->comment('tbl_projects.id');
                $table->decimal('labor_overrun', 15, 2)->default(0);
                $table->decimal('material_cost_overrun', 15, 2)->default(0);
                $table->decimal('contractor_claims', 15, 2)->default(0);
                $table->decimal('equipment_storage_charges', 15, 2)->default(0);
                $table->decimal('direct_cost_total', 15, 2)->default(0);
                $table->decimal('delayed_admissions', 15, 2)->default(0);
                $table->decimal('delayed_surgeries', 15, 2)->default(0);
                $table->decimal('delayed_revenue', 15, 2)->default(0);
                $table->decimal('lost_operational_days', 15, 2)->default(0);
                $table->decimal('opportunity_cost_total', 15, 2)->default(0);
                $table->decimal('total_project_delay_cost', 15, 2)->default(0);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('delay_register_id');
                $table->index('project_id');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_financial_impacts');
    }
}
