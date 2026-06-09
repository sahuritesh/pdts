<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allow device tokens without user_id (anonymous registration).
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_user_device_tokens')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tbl_user_device_tokens MODIFY user_id INT UNSIGNED NULL');
            try {
                DB::statement('ALTER TABLE tbl_user_device_tokens DROP INDEX unique_user_device');
            } catch (\Throwable $e) {
                // Index may not exist on this database
            }
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE tbl_user_device_tokens ALTER COLUMN user_id INT NULL');
            try {
                DB::statement("
                    IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'unique_user_device' AND object_id = OBJECT_ID('tbl_user_device_tokens'))
                    DROP INDEX unique_user_device ON tbl_user_device_tokens;
                ");
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('tbl_user_device_tokens')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tbl_user_device_tokens MODIFY user_id INT UNSIGNED NOT NULL');
            return;
        }

        if ($driver === 'sqlsrv') {
            DB::statement('
                ALTER TABLE tbl_user_device_tokens
                ALTER COLUMN user_id INT NOT NULL;
            ');
            try {
                DB::statement('
                    CREATE UNIQUE INDEX unique_user_device ON tbl_user_device_tokens(user_id, device_token);
                ');
            } catch (\Throwable $e) {
            }
        }
    }
};
