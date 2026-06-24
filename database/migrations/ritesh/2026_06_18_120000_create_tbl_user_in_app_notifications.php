<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portable in-app notifications table (copy with InAppNotifications module).
 * No DB foreign keys — relationships enforced in application layer.
 */
class CreateTblUserInAppNotifications extends Migration
{
    public function up()
    {
        $tableName = config('in_app_notifications.table', 'tbl_user_in_app_notifications');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->comment('Recipient — users table id');
            $table->string('notification_type', 50)->default('general');
            $table->string('title', 255);
            $table->text('message')->nullable();
            $table->string('entity_type', 50)->nullable()->comment('Domain entity key, e.g. delay_register');
            $table->integer('entity_id')->nullable()->comment('Domain entity id');
            $table->text('meta_json')->nullable()->comment('Optional JSON payload for app-specific data');
            $table->integer('triggered_by')->nullable()->comment('User who triggered the notification');
            $table->string('action_url', 500)->nullable();
            $table->string('action_mode', 20)->default('redirect')->comment('redirect, sidelayout');
            $table->tinyInteger('status')->default(0)->comment('0=unread, 1=read');
            $table->integer('created_by')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->dateTime('updated_on')->nullable();
            $table->tinyInteger('is_delete')->default(0);

            $table->index('user_id');
            $table->index('notification_type');
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index('status');
            $table->index('is_delete');
            $table->index(['user_id', 'status', 'is_delete']);
        });
    }

    public function down()
    {
        $tableName = config('in_app_notifications.table', 'tbl_user_in_app_notifications');
        Schema::dropIfExists($tableName);
    }
}
