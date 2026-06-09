<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sample projects and delays from Project_Delay_Framework_Renovation_Enhanced.xlsx
 * (Construction + Renovation Projects sheets).
 */
class PdtsFrameworkSampleDataSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $userId = 1;

        $this->seedConstructionProjects($now, $userId);
        $this->seedRenovationProjects($now, $userId);
    }

    private function seedConstructionProjects(Carbon $now, int $userId): void
    {
        if (DB::table('tbl_projects')->where('project_code', 'AH-Gurugram - CONST-01')->exists()) {
            return;
        }

        $greenFieldId = DB::table('tbl_project_types')->where('type_code', 'green_field')->value('id');
        $categoryRegulatory = $this->categoryId('Regulatory & Permitting');
        $categoryDesign = $this->categoryId('Design & Scope');
        $categorySupply = $this->categoryId('Supply Chain & Procurement');
        $rootPlanning = $this->rootCauseId('planning_gap');
        $rootDesign = $this->rootCauseId('design_change');
        $rootApproval = $this->rootCauseId('approval_bottleneck');

        $projects = [
            [
                'project_code' => 'AH-Gurugram - CONST-01',
                'project_name' => 'Apollo Gurugram Annex — Emergency Wing',
                'hospital_name' => 'Apollo Hospitals, Gurugram',
                'project_type_id' => $greenFieldId,
                'project_type_label' => 'Green Field',
                'area_facility' => 'Emergency',
                'project_spoc_name' => 'Mr.Biomedical',
                'planned_start_date' => $this->excelDate(46188),
                'planned_completion_date' => $this->excelDate(46194),
                'project_status' => 'delayed',
            ],
            [
                'project_code' => 'AH-Gurugram - CONST-02',
                'project_name' => 'Apollo Gurugram — MRI Room Fit-out',
                'hospital_name' => 'Apollo Hospitals, Gurugram',
                'project_type_id' => $greenFieldId,
                'project_type_label' => 'Green Field',
                'area_facility' => 'MRI Room',
                'project_spoc_name' => 'Mr.Operations',
                'planned_start_date' => $this->excelDate(46198),
                'planned_completion_date' => $this->excelDate(46203),
                'project_status' => 'delayed',
            ],
        ];

        $projectIds = [];
        foreach ($projects as $p) {
            $projectIds[$p['project_code']] = DB::table('tbl_projects')->insertGetId(array_merge($p, [
                'project_scope' => null,
                'location' => 'Gurugram',
                'contractor_name' => null,
                'zone_department' => $p['area_facility'],
                'responsible_user_id' => null,
                'responsibility_name' => $p['project_spoc_name'],
                'actual_completion_date' => null,
                'target_revised_completion_date' => null,
                'total_delay_cost' => 0,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]));
        }

        $delays = [
            [
                'project_code' => 'AH-Gurugram - CONST-01',
                'delay_title' => 'Pending Fire inspection for Annex building',
                'primary_delay_drivers' => 'Long wait times for environmental, fire safety, PCPNDT, AERB approvals',
                'specific_event_description' => 'Pending Fire inspection for Annex building',
                'impacted_task' => 'Final NOC',
                'root_cause_id' => $rootPlanning,
                'root_cause_label' => 'Planning Gap',
                'delay_category_id' => $categoryRegulatory,
                'delay_start_date' => null,
                'delay_end_date' => null,
                'delay_days' => 0,
                'severity' => 'showstopper',
                'licensing_openings_affected' => 1,
                'alert_level' => 'black',
                'escalation_level' => 4,
            ],
            [
                'project_code' => 'AH-Gurugram - CONST-02',
                'delay_title' => 'CSSD Uni-directional flow',
                'primary_delay_drivers' => 'Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment',
                'specific_event_description' => 'CSSD Uni-directional flow',
                'impacted_task' => 'ICU Headwall Installation',
                'root_cause_id' => $rootDesign,
                'root_cause_label' => 'Design Change',
                'responsibility_name' => 'Dr X',
                'delay_category_id' => $categoryDesign,
                'delay_start_date' => $this->excelDate(46056),
                'delay_end_date' => $this->excelDate(46073),
                'delay_days' => 17,
                'severity' => 'moderate',
                'licensing_openings_affected' => 0,
                'alert_level' => 'amber',
                'escalation_level' => 2,
                'mitigation_action' => "Change Order \n#42 outlets issued",
            ],
        ];

        foreach ($delays as $d) {
            $projectId = $projectIds[$d['project_code']];
            unset($d['project_code']);
            $mitigation = $d['mitigation_action'] ?? null;
            unset($d['mitigation_action']);

            $delayId = DB::table('tbl_delay_registers')->insertGetId(array_merge($d, [
                'project_id' => $projectId,
                'delay_description' => $d['specific_event_description'],
                'responsibility_user_id' => null,
                'register_status' => 'open',
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]));

            if ($mitigation) {
                DB::table('tbl_delay_mitigations')->insert([
                    'delay_register_id' => $delayId,
                    'mitigation_action' => $mitigation,
                    'owner_user_id' => null,
                    'owner_name' => null,
                    'target_resolution_date' => null,
                    'current_status' => 'in_progress',
                    'resolution_remarks' => null,
                    'created_by' => $userId,
                    'created_on' => $now,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ]);
            }
        }

        // Template delay category examples (no full project row in Excel — category reference only)
        $this->command->info('Construction sample projects seeded (Apollo Gurugram CONST-01, CONST-02).');
    }

    private function seedRenovationProjects(Carbon $now, int $userId): void
    {
        if (DB::table('tbl_renovation_projects')->where('project_code', 'REN-001')->exists()) {
            return;
        }

        $renoId = DB::table('tbl_renovation_projects')->insertGetId([
            'project_code' => 'REN-001',
            'project_name' => 'ICU Renovation',
            'project_scope' => 'Critical care area renovation',
            'location' => null,
            'zone_department_impacted' => 'ICU',
            'renovation_type' => 'Department Renovation',
            'project_status' => 'in_progress',
            'final_handover_date' => '2026-06-20',
            'escalation_status' => 'escalated',
            'remarks' => 'Critical care area',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        $taskId = DB::table('tbl_renovation_tasks')->insertGetId([
            'renovation_project_id' => $renoId,
            'task_category' => 'Construction',
            'task_description' => 'ICU renovation — primary critical path task',
            'priority' => 'high',
            'planned_start_date' => '2026-06-01',
            'planned_end_date' => '2026-06-15',
            'actual_start_date' => '2026-06-02',
            'actual_end_date' => '2026-06-18',
            'allocated_duration_days' => 14,
            'elapsed_duration_days' => 17,
            'task_completion_percent' => 75,
            'consumed_duration_percent' => 85,
            'task_status' => 'delayed',
            'risk_level' => 'high',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_operational_impacts')->insert([
            'renovation_project_id' => $renoId,
            'shutdown_required' => 'yes',
            'patient_service_disruption_score' => 9,
            'temporary_relocation_needed' => 'yes',
            'infection_control_clearance' => 'approved',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_task_dependencies')->insert([
            'renovation_task_id' => $taskId,
            'dependency_task_id' => 0,
            'dependency_status' => 'completed',
            'notes' => 'Design Approval',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        $riskRow = [
            'renovation_project_id' => $renoId,
            'renovation_task_id' => $taskId,
            'delay_days' => 3,
            'disruption_score' => 9,
            'approval_delay_days' => 7,
            'material_delay_days' => 5,
            'dependency_delay_days' => 0,
            'risk_level' => 'high',
            'assessment_notes' => 'Excel risk score 8 — mapped to high risk band',
            'assessed_on' => $now,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ];
        if (DB::getSchemaBuilder()->hasColumn('tbl_renovation_risk_assessments', 'risk_score')) {
            $riskRow['risk_score'] = 8;
        }
        DB::table('tbl_renovation_risk_assessments')->insert($riskRow);

        DB::table('tbl_renovation_procurements')->insert([
            'renovation_project_id' => $renoId,
            'renovation_task_id' => $taskId,
            'vendor_contractor' => 'ABC Infra',
            'procurement_status' => 'ordered',
            'material_delay_days' => 5,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_approvals')->insert([
            'renovation_project_id' => $renoId,
            'renovation_task_id' => $taskId,
            'approval_status' => 'pending',
            'approval_pending_days' => 7,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        foreach (['CO-001', 'CO-002'] as $coNum) {
            DB::table('tbl_renovation_change_orders')->insert([
                'renovation_project_id' => $renoId,
                'change_order_number' => $coNum,
                'change_description' => 'Scope adjustments per clinical feedback',
                'approval_status' => 'approved',
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }

        DB::table('tbl_renovation_cost_tracking')->insert([
            'renovation_project_id' => $renoId,
            'budgeted_cost' => 500000,
            'actual_cost' => 575000,
            'cost_overrun_percent' => 15.00,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_daily_delay_logs')->insert([
            'renovation_project_id' => $renoId,
            'renovation_task_id' => $taskId,
            'log_date' => '2026-06-10',
            'delay_reason' => 'Material shortage',
            'entered_by' => $userId,
            'corrective_action' => 'Expedited vendor delivery',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        // REN-002 OPD Upgrade
        $reno2Id = DB::table('tbl_renovation_projects')->insertGetId([
            'project_code' => 'REN-002',
            'project_name' => 'OPD Upgrade',
            'project_scope' => 'Outpatient department upgrade',
            'location' => null,
            'zone_department_impacted' => 'OPD',
            'renovation_type' => 'Department Upgrade',
            'project_status' => 'planned',
            'final_handover_date' => null,
            'escalation_status' => 'none',
            'remarks' => 'Minimal disruption',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        $task2Id = DB::table('tbl_renovation_tasks')->insertGetId([
            'renovation_project_id' => $reno2Id,
            'task_category' => 'Electrical',
            'task_description' => 'OPD electrical and fit-out works',
            'priority' => 'medium',
            'planned_start_date' => '2026-07-01',
            'planned_end_date' => '2026-07-10',
            'actual_start_date' => null,
            'actual_end_date' => null,
            'allocated_duration_days' => 9,
            'elapsed_duration_days' => 0,
            'task_completion_percent' => 0,
            'consumed_duration_percent' => 0,
            'task_status' => 'pending',
            'risk_level' => 'medium',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_operational_impacts')->insert([
            'renovation_project_id' => $reno2Id,
            'shutdown_required' => 'no',
            'patient_service_disruption_score' => 4,
            'temporary_relocation_needed' => 'no',
            'infection_control_clearance' => 'approved',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_task_dependencies')->insert([
            'renovation_task_id' => $task2Id,
            'dependency_task_id' => 0,
            'dependency_status' => 'pending',
            'notes' => 'Vendor Finalization',
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        $risk2 = [
            'renovation_project_id' => $reno2Id,
            'renovation_task_id' => $task2Id,
            'delay_days' => 0,
            'disruption_score' => 4,
            'approval_delay_days' => 0,
            'material_delay_days' => 0,
            'dependency_delay_days' => 0,
            'risk_level' => 'medium',
            'assessment_notes' => 'Excel risk score 5',
            'assessed_on' => $now,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ];
        if (DB::getSchemaBuilder()->hasColumn('tbl_renovation_risk_assessments', 'risk_score')) {
            $risk2['risk_score'] = 5;
        }
        DB::table('tbl_renovation_risk_assessments')->insert($risk2);

        DB::table('tbl_renovation_procurements')->insert([
            'renovation_project_id' => $reno2Id,
            'renovation_task_id' => $task2Id,
            'vendor_contractor' => 'XYZ Electrical',
            'procurement_status' => 'pending',
            'material_delay_days' => 0,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_approvals')->insert([
            'renovation_project_id' => $reno2Id,
            'renovation_task_id' => $task2Id,
            'approval_status' => 'approved',
            'approval_pending_days' => 0,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        DB::table('tbl_renovation_cost_tracking')->insert([
            'renovation_project_id' => $reno2Id,
            'budgeted_cost' => 150000,
            'actual_cost' => 0,
            'cost_overrun_percent' => 0,
            'created_by' => $userId,
            'created_on' => $now,
            'updated_by' => $userId,
            'updated_on' => $now,
            'is_delete' => 0,
        ]);

        $this->command->info('Renovation sample projects seeded (REN-001, REN-002).');
    }

    private function categoryId(string $name): ?int
    {
        return DB::table('tbl_delay_categories')
            ->where('category_name', $name)
            ->where('is_delete', 0)
            ->value('id');
    }

    private function rootCauseId(string $code): ?int
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_root_causes')) {
            return null;
        }
        return DB::table('tbl_root_causes')->where('cause_code', $code)->value('id');
    }

    private function excelDate($serial): ?string
    {
        if ($serial === null || $serial === '' || !is_numeric($serial)) {
            return null;
        }
        return Carbon::create(1899, 12, 30)->addDays((int) $serial)->format('Y-m-d');
    }
}
