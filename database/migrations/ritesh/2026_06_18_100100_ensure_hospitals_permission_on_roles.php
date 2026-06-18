<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureHospitalsPermissionOnRoles extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_roles')) {
            return;
        }

        $roles = DB::table('tbl_roles')->where('is_delete', 0)->get(['id', 'permission_types']);

        foreach ($roles as $role) {
            $permissions = array_filter(array_map('trim', explode(',', (string) ($role->permission_types ?? ''))));
            if ($permissions === [] || in_array('hospitals', $permissions, true)) {
                continue;
            }

            $shouldAdd = in_array('departments', $permissions, true)
                || in_array('locations', $permissions, true)
                || in_array('projects', $permissions, true)
                || in_array('roles', $permissions, true);

            if (!$shouldAdd) {
                continue;
            }

            $permissions[] = 'hospitals';
            DB::table('tbl_roles')->where('id', $role->id)->update([
                'permission_types' => implode(',', array_values(array_unique($permissions))),
                'updated_on' => now(),
            ]);
        }
    }

    public function down()
    {
        // Keep permission — removing may break access unexpectedly.
    }
}
