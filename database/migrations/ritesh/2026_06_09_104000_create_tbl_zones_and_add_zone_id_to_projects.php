<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Geographic zones master for projects (North, East, West, etc.). No DB foreign keys. */
class CreateTblZonesAndAddZoneIdToProjects extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_zones')) {
            Schema::create('tbl_zones', function (Blueprint $table) {
                $table->id();
                $table->string('zone_code', 50)->unique();
                $table->string('zone_name', 150);
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('status');
                $table->index('is_delete');
            });
        }

        if (Schema::hasTable('tbl_projects') && !Schema::hasColumn('tbl_projects', 'zone_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->integer('zone_id')->nullable()->after('zone_department')
                    ->comment('tbl_zones.id');
                $table->index('zone_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_projects') && Schema::hasColumn('tbl_projects', 'zone_id')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                $table->dropIndex(['zone_id']);
                $table->dropColumn('zone_id');
            });
        }

        Schema::dropIfExists('tbl_zones');
    }
}
