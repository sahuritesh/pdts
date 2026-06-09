<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Module 4 — Audit Trail (full change history). */
class CreateTblAuditTrails extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_audit_trails')) {
            Schema::create('tbl_audit_trails', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 100)->comment('e.g. delay_register, renovation_task');
                $table->unsignedBigInteger('entity_id');
                $table->string('action', 50)->comment('create, update, delete, status_change');
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->integer('created_by')->nullable()->comment('tbl_user.id');
                $table->dateTime('created_on')->nullable();
                $table->integer('modified_by')->nullable()->comment('tbl_user.id');
                $table->dateTime('modified_on')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();

                $table->index(['entity_type', 'entity_id']);
                $table->index('created_by');
                $table->index('created_on');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_audit_trails');
    }
}
