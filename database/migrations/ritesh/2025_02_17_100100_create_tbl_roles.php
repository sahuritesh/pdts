<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_roles')) {
            Schema::create('tbl_roles', function (Blueprint $table) {
                $table->id();
                $table->string('role_name', 100)->unique();
                $table->text('role_description')->nullable();
                $table->text('permission_types')->nullable()->comment('Comma-separated list of permissions');
                $table->integer('status')->default(1)->comment('1=Active, 2=Inactive');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0)->comment('0=Not Deleted, 1=Deleted');
                
                // Indexes
                $table->index('role_name');
                $table->index('status');
                $table->index('is_delete');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_roles');
    }
}

