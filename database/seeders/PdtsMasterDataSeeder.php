<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master data aligned with Project_Delay_Framework_Renovation_Enhanced.xlsx
 */
class PdtsMasterDataSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $userId = 1;

        $this->seedProjectTypes($now, $userId);
        $this->seedZones($now, $userId);
        $this->seedRootCauses($now, $userId);
        $this->seedDelayCategories($now, $userId);
        $this->seedSeverityRules($now, $userId);
        $this->seedAlertLevels($now, $userId);
        $this->seedEscalationMatrix($now, $userId);
        $this->seedPredictionConfig($now, $userId);
    }

    private function seedProjectTypes($now, int $userId): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_project_types')) {
            return;
        }

        $types = [
            ['green_field', 'Green Field', 'New hospital construction on undeveloped land'],
            ['brown_field', 'Brown Field', 'Expansion or rebuild on existing hospital site'],
            ['renovation', 'Renovation', 'Renovation of existing departments or facilities'],
        ];

        foreach ($types as [$code, $name, $desc]) {
            $exists = DB::table('tbl_project_types')->where('type_code', $code)->exists();
            if ($exists) {
                continue;
            }
            DB::table('tbl_project_types')->insert([
                'type_code' => $code,
                'type_name' => $name,
                'description' => $desc,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedZones($now, int $userId): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_zones')) {
            return;
        }

        $zones = [
            ['north', 'North Zone', 'Northern region hospitals and projects'],
            ['south', 'South Zone', 'Southern region hospitals and projects'],
            ['east', 'East Zone', 'Eastern region hospitals and projects'],
            ['west', 'West Zone', 'Western region hospitals and projects'],
            ['north_east', 'North East Zone', 'North eastern region hospitals and projects'],
            ['north_west', 'North West Zone', 'North western region hospitals and projects'],
            ['south_east', 'South East Zone', 'South eastern region hospitals and projects'],
            ['south_west', 'South West Zone', 'South western region hospitals and projects'],
            ['central', 'Central Zone', 'Central region hospitals and projects'],
        ];

        foreach ($zones as [$code, $name, $desc]) {
            if (DB::table('tbl_zones')->where('zone_code', $code)->exists()) {
                continue;
            }
            DB::table('tbl_zones')->insert([
                'zone_code' => $code,
                'zone_name' => $name,
                'description' => $desc,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedRootCauses($now, int $userId): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_root_causes')) {
            return;
        }

        $causes = [
            ['planning_gap', 'Planning Gap', 'Planning gap or incomplete upfront planning'],
            ['coordination_failure', 'Coordination Failure', 'Coordination failure between stakeholders'],
            ['vendor_underperformance', 'Vendor Underperformance', 'Vendor or contractor underperformance'],
            ['design_change', 'Design Change', 'Mid-project design or scope change'],
            ['approval_bottleneck', 'Approval Bottleneck', 'Approval or regulatory bottleneck'],
            ['resource_constraint', 'Resource Constraint', 'Labor, material, or resource constraint'],
        ];

        foreach ($causes as [$code, $name, $desc]) {
            if (DB::table('tbl_root_causes')->where('cause_code', $code)->exists()) {
                continue;
            }
            DB::table('tbl_root_causes')->insert([
                'cause_code' => $code,
                'cause_name' => $name,
                'description' => $desc,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedDelayCategories($now, int $userId): void
    {
        // From Excel Sheet1 + Construction tab (unified category buckets)
        $categories = [
            ['Regulatory & Permitting', 'Long wait times for environmental, fire safety, PCPNDT, AERB approvals'],
            ['MEP', 'Mechanical, electrical, and plumbing delays affecting critical path'],
            ['Supply Chain & Procurement', 'Long-lead items (MRI, custom AHUs) delayed by vendor halting final inspection'],
            ['Design & Scope', 'Mid-construction changes; late-stage clinician requests for layout or equipment'],
            ['Medical Equipment Installations', 'Delays in medical equipment delivery, installation, or commissioning'],
            ['Specialized Labor', 'Shortage of certified manpower (e.g. lead-shielding installers)'],
            ['Logistical Challenges', 'Material movement blocked by active patient traffic; night-work delays'],
            ['Site Condition Surprises', 'Unplanned structural, plumbing, or site discovery issues'],
            ['Operational Readiness', 'Staff hiring/training delays; IT systems not ready for go-live'],
            ['Infection Control Compliance', 'Dust barriers, air pressure, or infection control clearance delays'],
        ];

        $excelNames = array_column($categories, 0);

        // Deactivate legacy generic categories not in Excel framework
        DB::table('tbl_delay_categories')
            ->where('is_delete', 0)
            ->whereNotIn('category_name', $excelNames)
            ->update([
                'status' => 0,
                'is_delete' => 1,
                'updated_by' => $userId,
                'updated_on' => $now,
            ]);

        foreach ($categories as [$name, $description]) {
            $row = DB::table('tbl_delay_categories')->where('category_name', $name)->first();
            if ($row) {
                DB::table('tbl_delay_categories')->where('id', $row->id)->update([
                    'description' => $description,
                    'status' => 1,
                    'is_delete' => 0,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                ]);
                continue;
            }
            DB::table('tbl_delay_categories')->insert([
                'category_name' => $name,
                'description' => $description,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedSeverityRules($now, int $userId): void
    {
        $rules = [
            ['minor', 'Minor (1-7 days)', 1, 7, 0, 1],
            ['moderate', 'Moderate (8-30 days)', 8, 30, 0, 2],
            ['critical', 'Critical (>30 days)', 31, null, 0, 3],
            ['showstopper', 'Showstopper (impacts licensing/opening)', null, null, 1, 4],
        ];

        foreach ($rules as [$code, $label, $min, $max, $licensing, $escalation]) {
            $exists = DB::table('tbl_delay_severity_rules')->where('severity_code', $code)->exists();
            $payload = [
                'severity_label' => $label,
                'min_delay_days' => $min,
                'max_delay_days' => $max,
                'requires_licensing_flag' => $licensing,
                'default_escalation_level' => $escalation,
                'sort_order' => $escalation,
                'status' => 1,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ];
            if ($exists) {
                DB::table('tbl_delay_severity_rules')->where('severity_code', $code)->update($payload);
            } else {
                DB::table('tbl_delay_severity_rules')->insert(array_merge($payload, [
                    'severity_code' => $code,
                    'created_by' => $userId,
                    'created_on' => $now,
                ]));
            }
        }
    }

    private function seedAlertLevels($now, int $userId): void
    {
        $levels = [
            ['green', 'Green — On Track', 1],
            ['amber', 'Amber — Potential Delay', 2],
            ['red', 'Red — Critical Delay', 3],
            ['black', 'Black — Showstopper', 4],
        ];

        foreach ($levels as [$code, $label, $sort]) {
            if (DB::table('tbl_ews_alert_levels')->where('level_code', $code)->exists()) {
                continue;
            }
            DB::table('tbl_ews_alert_levels')->insert([
                'level_code' => $code,
                'level_label' => $label,
                'sort_order' => $sort,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedEscalationMatrix($now, int $userId): void
    {
        $rows = [
            [1, 'Project SPOC', 'minor', 'green'],
            [2, 'Department Head', 'moderate', 'amber'],
            [3, 'Project Steering Committee', 'critical', 'red'],
            [4, 'Management', 'showstopper', 'black'],
        ];

        foreach ($rows as [$level, $role, $severity, $alert]) {
            if (DB::table('tbl_ews_escalation_matrix')->where('escalation_level', $level)->exists()) {
                continue;
            }
            DB::table('tbl_ews_escalation_matrix')->insert([
                'escalation_level' => $level,
                'escalation_role' => $role,
                'trigger_severity' => $severity,
                'trigger_alert_level' => $alert,
                'status' => 1,
                'created_by' => $userId,
                'created_on' => $now,
                'updated_by' => $userId,
                'updated_on' => $now,
                'is_delete' => 0,
            ]);
        }
    }

    private function seedPredictionConfig($now, int $userId): void
    {
        $configs = [
            ['max_task_completion_percent', '50', 'Task completion must be below this % for EWS alert'],
            ['min_consumed_duration_percent', '80', 'Consumed duration must exceed this % for EWS alert'],
        ];

        foreach ($configs as [$key, $value, $desc]) {
            if (DB::table('tbl_ews_prediction_config')->where('config_key', $key)->exists()) {
                continue;
            }
            DB::table('tbl_ews_prediction_config')->insert([
                'config_key' => $key,
                'config_value' => $value,
                'description' => $desc,
                'status' => 1,
                'updated_by' => $userId,
                'updated_on' => $now,
            ]);
        }
    }
}
