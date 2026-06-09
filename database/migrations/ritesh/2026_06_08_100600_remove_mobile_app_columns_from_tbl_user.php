<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveMobileAppColumnsFromTblUser extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_user')) {
            return;
        }

        $columns = ['api_token', 'device_id', 'mobile_app_version', 'mobile_app_name', 'is_mobile_enabled'];
        $drop = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn('tbl_user', $column)) {
                $drop[] = $column;
            }
        }

        if ($drop !== []) {
            Schema::table('tbl_user', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('tbl_user')) {
            return;
        }

        Schema::table('tbl_user', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_user', 'api_token')) {
                $table->string('api_token', 100)->nullable();
            }
            if (!Schema::hasColumn('tbl_user', 'device_id')) {
                $table->string('device_id', 255)->nullable();
            }
            if (!Schema::hasColumn('tbl_user', 'mobile_app_version')) {
                $table->string('mobile_app_version', 50)->nullable();
            }
            if (!Schema::hasColumn('tbl_user', 'mobile_app_name')) {
                $table->string('mobile_app_name', 50)->nullable();
            }
            if (!Schema::hasColumn('tbl_user', 'is_mobile_enabled')) {
                $table->tinyInteger('is_mobile_enabled')->default(0);
            }
        });
    }
}
