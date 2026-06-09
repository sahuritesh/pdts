<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 3 — Change Order Tracking. */
class CreateTblRenovationChangeOrders extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_renovation_change_orders')) {
            Schema::create('tbl_renovation_change_orders', function (Blueprint $table) {
                $table->id();
                $table->integer('renovation_project_id')->comment('tbl_renovation_projects.id');
                $table->string('change_order_number', 50)->nullable();
                $table->text('change_description')->nullable();
                $table->string('approval_status', 30)->default('pending')->comment('pending, approved, rejected');
                $table->decimal('change_cost', 15, 2)->nullable();
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
        Schema::dropIfExists('tbl_renovation_change_orders');
    }
}
