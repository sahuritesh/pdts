<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAllowParallelNextToProjectDepartments extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_project_departments') && !Schema::hasColumn('tbl_project_departments', 'allow_parallel_next')) {
            Schema::table('tbl_project_departments', function (Blueprint $table) {
                $table->unsignedTinyInteger('allow_parallel_next')->default(0)->after('sort_order')
                    ->comment('1 = next department may start before this one completes');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_project_departments') && Schema::hasColumn('tbl_project_departments', 'allow_parallel_next')) {
            Schema::table('tbl_project_departments', function (Blueprint $table) {
                $table->dropColumn('allow_parallel_next');
            });
        }
    }
}
