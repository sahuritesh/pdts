<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('tbl_user')) {
            Schema::create('tbl_user', function (Blueprint $table) {
                $table->id();
                $table->string('username', 100)->unique()->nullable();
                $table->string('email_id', 200)->nullable();
                $table->string('password', 255);
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('mobile_no', 20)->nullable();
                $table->integer('user_type')->default(1)->comment('FK to tbl_roles.id');
                $table->integer('status')->default(1)->comment('1=Active, 2=Inactive');
                $table->string('remember_token', 100)->nullable();
                $table->dateTime('last_logged_on')->nullable();
                $table->string('profile_image', 255)->nullable();
                $table->text('address')->nullable();
                $table->integer('reference_id')->nullable()->comment('ID from the related registration table');
                $table->string('serial_number', 50)->nullable()->comment('Serial number from the registration table');
                $table->string('qr_code', 255)->nullable();
                $table->string('api_token', 100)->nullable()->comment('API token for mobile app authentication');
                $table->string('device_id', 255)->nullable()->comment('Device ID for mobile app');
                $table->string('mobile_app_version', 50)->nullable()->comment('Mobile app version');
                $table->string('mobile_app_name', 50)->nullable()->comment('Mobile app name');
                $table->tinyInteger('is_mobile_enabled')->default(0)->comment('1 = Enabled for mobile app, 0 = Disabled');
                $table->string('otp_code', 10)->nullable()->comment('OTP code for password reset');
                $table->dateTime('otp_expiry')->nullable()->comment('OTP expiry date and time');
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0)->comment('0=Not Deleted, 1=Deleted');
                
                // Indexes
                $table->index('username');
                $table->index('email_id');
                $table->index('user_type');
                $table->index('status');
                $table->index('mobile_no');
                $table->index('reference_id');
                $table->index('serial_number');
                $table->index('api_token');
                $table->index('device_id');
                $table->index('is_mobile_enabled');
                $table->index('is_delete');
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
        Schema::dropIfExists('tbl_user');
    }
}

