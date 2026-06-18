<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsolidateSpocRoles extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_roles') || !Schema::hasTable('tbl_user')) {
            return;
        }

        $deptRoleId = DB::table('tbl_roles')
            ->where('role_name', 'Department SPOC')
            ->where('is_delete', 0)
            ->value('id');

        $projectRoleId = DB::table('tbl_roles')
            ->where('role_name', 'Project SPOC')
            ->where('is_delete', 0)
            ->value('id');

        if (!$deptRoleId || !$projectRoleId || (int) $deptRoleId === (int) $projectRoleId) {
            return;
        }

        DB::table('tbl_user')
            ->where('user_type', (int) $projectRoleId)
            ->update([
                'user_type' => (int) $deptRoleId,
                'updated_on' => now(),
            ]);

        DB::table('tbl_roles')->where('id', (int) $projectRoleId)->update([
            'is_delete' => 1,
            'status' => 2,
            'updated_on' => now(),
        ]);
    }

    public function down()
    {
        // Do not restore duplicate role.
    }
}
