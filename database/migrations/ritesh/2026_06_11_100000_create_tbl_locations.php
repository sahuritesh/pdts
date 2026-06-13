<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblLocations extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_locations')) {
            Schema::create('tbl_locations', function (Blueprint $table) {
                $table->id();
                $table->string('location_code', 50)->unique();
                $table->string('location_name', 255);
                $table->integer('zone_id')->comment('tbl_zones.id');
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('zone_id');
                $table->index('status');
                $table->index('is_delete');
            });
        }

        if (Schema::hasTable('tbl_projects') && !Schema::hasColumn('tbl_projects', 'location_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->integer('location_id')->nullable()->after('zone_id')->comment('tbl_locations.id');
                $table->index('location_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_projects') && Schema::hasColumn('tbl_projects', 'location_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->dropIndex(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        Schema::dropIfExists('tbl_locations');
    }
}
