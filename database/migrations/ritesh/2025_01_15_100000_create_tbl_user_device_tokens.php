<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_user_device_tokens')) {
            Schema::create('tbl_user_device_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('device_token', 500);
                $table->string('device_id', 255)->nullable();
                $table->string('platform', 20)->default('android');
                $table->string('app_version', 50)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->dateTime('last_used_at')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->dateTime('updated_on')->nullable();

                $table->index('user_id', 'IX_user_device_tokens_user_id');
                $table->index('device_token', 'IX_user_device_tokens_device_token');
                $table->index(['user_id', 'status'], 'IX_user_device_tokens_user_status');
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
        Schema::dropIfExists('tbl_user_device_tokens');
    }
};
