<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblUserDepartments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_user_departments')) {
            Schema::create('tbl_user_departments', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->comment('tbl_user.id');
                $table->integer('department_id')->comment('tbl_departments.id');
                $table->tinyInteger('is_primary')->default(1);
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index(['user_id', 'department_id']);
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_user_departments');
    }
}
