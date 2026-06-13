<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes Excel/framework sample projects, legacy delay rows, and renovation demo data.
 * Keeps real wizard data (rows linked via project_department_id on active projects).
 */
class RemoveSampleDataSeeder extends Seeder
{
    private array $sampleProjectCodes = [
        'AH-Gurugram - CONST-01',
    ];

    private array $sampleDelayTitles = [
        'Pending Fire inspection for Annex building',
        'CSSD Uni-directional flow',
    ];

    private array $sampleRenovationCodes = [
        'REN-001',
        'REN-002',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $this->removeSampleProjects();
            $this->removeLegacyDelayRows();
            $this->removeRenovationSampleData();
        });

        if (Schema::hasTable('tbl_project_departments')) {
            app(\App\Services\ProjectDepartmentService::class)->syncAllProjectRollupStatuses();
        }

        $this->command->info('Sample / dummy PDTS data removed.');
    }

    private function removeSampleProjects(): void
    {
        if (!Schema::hasTable('tbl_projects')) {
            return;
        }

        $projectIds = DB::table('tbl_projects')
            ->whereIn('project_code', $this->sampleProjectCodes)
            ->pluck('id')
            ->all();

        foreach ($projectIds as $projectId) {
            $this->purgeProject((int) $projectId);
            DB::table('tbl_projects')->where('id', $projectId)->delete();
            $this->command->info("Removed sample project id {$projectId}");
        }
    }

    private function purgeProject(int $projectId): void
    {
        $delayIds = DB::table('tbl_delay_registers')
            ->where('project_id', $projectId)
            ->pluck('id')
            ->all();

        $this->deleteDelayChildren($delayIds);

        if (Schema::hasTable('tbl_project_departments')) {
            DB::table('tbl_project_departments')->where('project_id', $projectId)->delete();
        }

        if (Schema::hasTable('tbl_delay_financial_impacts')) {
            DB::table('tbl_delay_financial_impacts')->where('project_id', $projectId)->delete();
        }

        if (Schema::hasTable('tbl_delay_attachments')) {
            DB::table('tbl_delay_attachments')->where('project_id', $projectId)->delete();
        }

        DB::table('tbl_delay_registers')->where('project_id', $projectId)->delete();
    }

    private function removeLegacyDelayRows(): void
    {
        if (!Schema::hasTable('tbl_delay_registers')) {
            return;
        }

        $query = DB::table('tbl_delay_registers')->where('is_delete', 0);

        $legacyIds = (clone $query)
            ->where(function ($q) {
                $q->whereIn('delay_title', $this->sampleDelayTitles);
                if (Schema::hasColumn('tbl_delay_registers', 'project_department_id')) {
                    $q->orWhereNull('project_department_id');
                }
            })
            ->pluck('id')
            ->all();

        if (empty($legacyIds)) {
            return;
        }

        $this->deleteDelayChildren($legacyIds);
        DB::table('tbl_delay_registers')->whereIn('id', $legacyIds)->delete();
        $this->command->info('Removed ' . count($legacyIds) . ' legacy delay register row(s).');
    }

    private function deleteDelayChildren(array $delayIds): void
    {
        if (empty($delayIds)) {
            return;
        }

        if (Schema::hasTable('tbl_delay_mitigations')) {
            DB::table('tbl_delay_mitigations')->whereIn('delay_register_id', $delayIds)->delete();
        }

        if (Schema::hasTable('tbl_delay_financial_impacts')) {
            DB::table('tbl_delay_financial_impacts')->whereIn('delay_register_id', $delayIds)->delete();
        }

        if (Schema::hasTable('tbl_delay_attachments')) {
            DB::table('tbl_delay_attachments')->whereIn('delay_register_id', $delayIds)->delete();
        }
    }

    private function removeRenovationSampleData(): void
    {
        if (!Schema::hasTable('tbl_renovation_projects')) {
            return;
        }

        $renoIds = DB::table('tbl_renovation_projects')
            ->whereIn('project_code', $this->sampleRenovationCodes)
            ->pluck('id')
            ->all();

        if (empty($renoIds)) {
            return;
        }

        $taskIds = Schema::hasTable('tbl_renovation_tasks')
            ? DB::table('tbl_renovation_tasks')->whereIn('renovation_project_id', $renoIds)->pluck('id')->all()
            : [];

        foreach ([
            'tbl_renovation_daily_delay_logs',
            'tbl_renovation_approvals',
            'tbl_renovation_procurements',
            'tbl_renovation_risk_assessments',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'renovation_project_id')) {
                DB::table($table)->whereIn('renovation_project_id', $renoIds)->delete();
            }
        }

        if (!empty($taskIds)) {
            if (Schema::hasTable('tbl_renovation_task_dependencies')) {
                DB::table('tbl_renovation_task_dependencies')
                    ->whereIn('renovation_task_id', $taskIds)
                    ->delete();
            }
            if (Schema::hasTable('tbl_renovation_tasks')) {
                DB::table('tbl_renovation_tasks')->whereIn('id', $taskIds)->delete();
            }
        }

        foreach ([
            'tbl_renovation_operational_impacts',
            'tbl_renovation_change_orders',
            'tbl_renovation_cost_tracking',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'renovation_project_id')) {
                DB::table($table)->whereIn('renovation_project_id', $renoIds)->delete();
            }
        }

        DB::table('tbl_renovation_projects')->whereIn('id', $renoIds)->delete();
        $this->command->info('Removed renovation sample projects (REN-001, REN-002).');
    }
}
