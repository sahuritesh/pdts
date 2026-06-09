<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove affiliation, privacy_settings, registration_type from tbl_user.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_user')) {
            return;
        }

        $this->dropIndexIfExists('tbl_user', 'tbl_user_registration_type_index');
        $this->dropIndexIfExists('tbl_user', 'tbl_user_registration_type_reference_id_index');

        $drop = [];
        foreach (['registration_type', 'affiliation', 'privacy_settings'] as $col) {
            if (Schema::hasColumn('tbl_user', $col)) {
                $drop[] = $col;
            }
        }

        if ($drop !== []) {
            Schema::table('tbl_user', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
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
        if (!Schema::hasTable('tbl_user')) {
            return;
        }

        if (!Schema::hasColumn('tbl_user', 'affiliation')) {
            Schema::table('tbl_user', function (Blueprint $table) {
                $table->text('affiliation')->nullable();
            });
        }
        if (!Schema::hasColumn('tbl_user', 'privacy_settings')) {
            Schema::table('tbl_user', function (Blueprint $table) {
                $table->text('privacy_settings')->nullable();
            });
        }
        if (!Schema::hasColumn('tbl_user', 'registration_type')) {
            Schema::table('tbl_user', function (Blueprint $table) {
                $table->string('registration_type', 50)->nullable();
            });
        }

        if (Schema::hasColumn('tbl_user', 'registration_type')) {
            Schema::table('tbl_user', function (Blueprint $table) {
                $table->index('registration_type');
                $table->index(['registration_type', 'reference_id']);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $exists = DB::select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$table, $indexName]
        );

        if (!empty($exists)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};
