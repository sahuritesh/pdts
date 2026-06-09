<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DeactivateAllDeviceTokensForFirebaseProjectMigration extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration deactivates all existing device tokens when switching Firebase projects.
     * Device tokens are tied to a specific Firebase project's sender ID. When switching projects,
     * all existing tokens become invalid and need to be re-registered with the new project.
     *
     * @return void
     */
    public function up()
    {
        // Deactivate all active device tokens
        // Users will need to re-register their device tokens with the new Firebase project
        $deactivatedCount = DB::table('tbl_user_device_tokens')
            ->where('status', 1) // ACTIVE
            ->update([
                'status' => 0, // INACTIVE
                'updated_on' => now()
            ]);

        // Log the action (if logging is available)
        if (function_exists('Log')) {
            \Illuminate\Support\Facades\Log::info('Deactivated all device tokens for Firebase project migration', [
                'deactivated_count' => $deactivatedCount
            ]);
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This cannot be safely reversed as we don't know which tokens were
     * originally active. This migration is intended to be run once when switching
     * Firebase projects.
     *
     * @return void
     */
    public function down()
    {
        // Cannot safely reverse - tokens need to be re-registered with new project
        // Do nothing
    }
}
