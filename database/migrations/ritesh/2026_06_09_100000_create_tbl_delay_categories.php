<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** No database foreign keys — app validates tbl_* references. */
class CreateTblDelayCategories extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_categories')) {
            Schema::create('tbl_delay_categories', function (Blueprint $table) {
                $table->id();
                $table->string('category_name', 150);
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('category_name');
                $table->index('status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_categories');
    }
}
