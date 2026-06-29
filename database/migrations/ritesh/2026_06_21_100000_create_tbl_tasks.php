<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Global task master catalog — no department or project linkage. No DB foreign keys. */
class CreateTblTasks extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_tasks')) {
            Schema::create('tbl_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('task_name', 255);
                $table->string('task_code', 50)->nullable();
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1)->comment('1 active, 0 inactive');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('task_name');
                $table->index('task_code');
                $table->index('status');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_tasks');
    }
}
