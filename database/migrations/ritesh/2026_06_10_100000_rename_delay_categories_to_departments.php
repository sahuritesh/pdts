<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDelayCategoriesToDepartments extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_delay_categories') && !Schema::hasTable('tbl_departments')) {
            Schema::rename('tbl_delay_categories', 'tbl_departments');
        }

        if (Schema::hasTable('tbl_departments') && Schema::hasColumn('tbl_departments', 'category_name')) {
            Schema::table('tbl_departments', function (Blueprint $table) {
                $table->renameColumn('category_name', 'department_name');
            });
        }

        if (Schema::hasTable('tbl_departments') && !Schema::hasColumn('tbl_departments', 'default_sort_order')) {
            Schema::table('tbl_departments', function (Blueprint $table) {
                $table->unsignedSmallInteger('default_sort_order')->default(0)->after('description');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_departments') && Schema::hasColumn('tbl_departments', 'default_sort_order')) {
            Schema::table('tbl_departments', function (Blueprint $table) {
                $table->dropColumn('default_sort_order');
            });
        }

        if (Schema::hasTable('tbl_departments') && Schema::hasColumn('tbl_departments', 'department_name')) {
            Schema::table('tbl_departments', function (Blueprint $table) {
                $table->renameColumn('department_name', 'category_name');
            });
        }

        if (Schema::hasTable('tbl_departments') && !Schema::hasTable('tbl_delay_categories')) {
            Schema::rename('tbl_departments', 'tbl_delay_categories');
        }
    }
}
