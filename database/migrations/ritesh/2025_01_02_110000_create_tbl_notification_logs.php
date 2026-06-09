<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblNotificationLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_notification_logs')) {
            Schema::create('tbl_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->enum('notification_type', [
                    'specific_user',
                    'multiple_users',
                    'all_users',
                    'by_role',
                    'by_event',
                    'by_conference',
                    'by_session',
                    'by_platform',
                    'anonymous'
                ])->comment('Type of notification sent');
                $table->string('title', 255)->comment('Notification title');
                $table->text('body')->comment('Notification body');
                $table->integer('sent_count')->default(0)->comment('Number of notifications sent successfully');
                $table->integer('failed_count')->default(0)->comment('Number of notifications failed');
                $table->integer('total_count')->default(0)->comment('Total number of notifications attempted');
                $table->unsignedInteger('created_by')->nullable()->comment('FK to tbl_user - Admin who sent the notification');
                $table->dateTime('created_on')->nullable();
                
                // Indexes
                $table->index('notification_type');
                $table->index('created_by');
                $table->index('created_on');
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
        Schema::dropIfExists('tbl_notification_logs');
    }
}

