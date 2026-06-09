<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align PDTS schema with Project_Delay_Framework_Renovation_Enhanced.xlsx
 * (master lookups + fields present in Excel but missing from initial FRS migrations).
 */
class AlignSchemaWithExcelFramework extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_project_types')) {
            Schema::create('tbl_project_types', function (Blueprint $table) {
                $table->id();
                $table->string('type_code', 50)->unique();
                $table->string('type_name', 150);
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);
                $table->index('status');
                $table->index('is_delete');
            });
        }

        if (!Schema::hasTable('tbl_root_causes')) {
            Schema::create('tbl_root_causes', function (Blueprint $table) {
                $table->id();
                $table->string('cause_code', 50)->unique();
                $table->string('cause_name', 150);
                $table->text('description')->nullable();
                $table->integer('status')->default(1);
                $table->integer('created_by')->nullable();
                $table->dateTime('created_on')->nullable();
                $table->integer('updated_by')->nullable();
                $table->dateTime('updated_on')->nullable();
                $table->tinyInteger('is_delete')->default(0);
                $table->index('status');
                $table->index('is_delete');
            });
        }

        if (Schema::hasTable('tbl_projects')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_projects', 'project_type_id')) {
                    $table->integer('project_type_id')->nullable()->after('project_name')
                        ->comment('tbl_project_types.id');
                }
                if (!Schema::hasColumn('tbl_projects', 'project_type_label')) {
                    $table->string('project_type_label', 100)->nullable()->after('project_type_id')
                        ->comment('Green Field, Brown Field, Renovation');
                }
                if (!Schema::hasColumn('tbl_projects', 'area_facility')) {
                    $table->string('area_facility', 255)->nullable()->after('zone_department')
                        ->comment('Area / Facility per Excel');
                }
                if (!Schema::hasColumn('tbl_projects', 'project_spoc_name')) {
                    $table->string('project_spoc_name', 255)->nullable()->after('responsibility_name')
                        ->comment('Project SPOC display name');
                }
                if (!Schema::hasColumn('tbl_projects', 'target_revised_completion_date')) {
                    $table->date('target_revised_completion_date')->nullable()->after('actual_completion_date');
                }
            });
        }

        if (Schema::hasTable('tbl_delay_registers')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_delay_registers', 'primary_delay_drivers')) {
                    $table->text('primary_delay_drivers')->nullable()->after('delay_description');
                }
                if (!Schema::hasColumn('tbl_delay_registers', 'specific_event_description')) {
                    $table->text('specific_event_description')->nullable()->after('primary_delay_drivers');
                }
                if (!Schema::hasColumn('tbl_delay_registers', 'impacted_task')) {
                    $table->string('impacted_task', 255)->nullable()->after('specific_event_description')
                        ->comment('Critical path / impacted task');
                }
                if (!Schema::hasColumn('tbl_delay_registers', 'root_cause_id')) {
                    $table->integer('root_cause_id')->nullable()->after('impacted_task')
                        ->comment('tbl_root_causes.id');
                }
                if (!Schema::hasColumn('tbl_delay_registers', 'root_cause_label')) {
                    $table->string('root_cause_label', 150)->nullable()->after('root_cause_id');
                }
                if (!Schema::hasColumn('tbl_delay_registers', 'target_revised_completion_date')) {
                    $table->date('target_revised_completion_date')->nullable()->after('delay_end_date');
                }
            });
        }

        if (Schema::hasTable('tbl_renovation_projects')) {
            Schema::table('tbl_renovation_projects', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_renovation_projects', 'final_handover_date')) {
                    $table->date('final_handover_date')->nullable()->after('project_status');
                }
                if (!Schema::hasColumn('tbl_renovation_projects', 'escalation_status')) {
                    $table->string('escalation_status', 50)->nullable()->after('final_handover_date')
                        ->comment('none, escalated, etc.');
                }
                if (!Schema::hasColumn('tbl_renovation_projects', 'remarks')) {
                    $table->text('remarks')->nullable()->after('escalation_status');
                }
            });
        }

        if (Schema::hasTable('tbl_renovation_risk_assessments')) {
            Schema::table('tbl_renovation_risk_assessments', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_renovation_risk_assessments', 'risk_score')) {
                    $table->unsignedTinyInteger('risk_score')->nullable()->after('dependency_delay_days')
                        ->comment('Numeric score 1-10 per Excel');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_renovation_risk_assessments') && Schema::hasColumn('tbl_renovation_risk_assessments', 'risk_score')) {
            Schema::table('tbl_renovation_risk_assessments', function (Blueprint $table) {
                $table->dropColumn('risk_score');
            });
        }

        if (Schema::hasTable('tbl_renovation_projects')) {
            Schema::table('tbl_renovation_projects', function (Blueprint $table) {
                foreach (['remarks', 'escalation_status', 'final_handover_date'] as $col) {
                    if (Schema::hasColumn('tbl_renovation_projects', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('tbl_delay_registers')) {
            Schema::table('tbl_delay_registers', function (Blueprint $table) {
                foreach ([
                    'target_revised_completion_date', 'root_cause_label', 'root_cause_id',
                    'impacted_task', 'specific_event_description', 'primary_delay_drivers',
                ] as $col) {
                    if (Schema::hasColumn('tbl_delay_registers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('tbl_projects')) {
            Schema::table('tbl_projects', function (Blueprint $table) {
                foreach ([
                    'target_revised_completion_date', 'project_spoc_name', 'area_facility',
                    'project_type_label', 'project_type_id',
                ] as $col) {
                    if (Schema::hasColumn('tbl_projects', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('tbl_root_causes');
        Schema::dropIfExists('tbl_project_types');
    }
}
