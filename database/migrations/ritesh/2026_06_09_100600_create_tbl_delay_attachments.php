<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 1 — Attachments (photos, drawings, NOCs, etc.). */
class CreateTblDelayAttachments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_delay_attachments')) {
            Schema::create('tbl_delay_attachments', function (Blueprint $table) {
                $table->id();
                $table->integer('delay_register_id')->comment('tbl_delay_registers.id');
                $table->integer('project_id')->nullable()->comment('tbl_projects.id');
                $table->string('attachment_type', 50)->comment('photo, drawing, noc, approval_letter, vendor_communication, change_order, other');
                $table->string('file_name', 255);
                $table->string('file_path', 500);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->text('description')->nullable();
                $table->integer('uploaded_by')->nullable()->comment('tbl_user.id');
                $table->dateTime('uploaded_on')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);

                $table->index('delay_register_id');
                $table->index('project_id');
                $table->index('attachment_type');
                $table->index('is_delete');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_delay_attachments');
    }
}
