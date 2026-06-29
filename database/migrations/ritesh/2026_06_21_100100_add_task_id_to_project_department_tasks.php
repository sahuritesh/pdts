<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Link wizard task rows to tbl_tasks master. Backfills existing task_name values. */
class AddTaskIdToProjectDepartmentTasks extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_project_department_tasks')) {
            return;
        }

        if (!Schema::hasColumn('tbl_project_department_tasks', 'task_id')) {
            Schema::table('tbl_project_department_tasks', function (Blueprint $table) {
                $table->integer('task_id')->nullable()->after('sort_order')
                    ->comment('tbl_tasks.id — master task catalog');
                $table->index('task_id');
            });
        }

        if (!Schema::hasTable('tbl_tasks')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $userId = 1;

        $rows = DB::table('tbl_project_department_tasks')
            ->whereNull('task_id')
            ->where('is_delete', 0)
            ->get(['id', 'task_name']);

        foreach ($rows as $row) {
            $name = trim((string) ($row->task_name ?? ''));
            if ($name === '') {
                continue;
            }

            $normalized = mb_strtolower($name);
            $masterId = DB::table('tbl_tasks')
                ->where('is_delete', 0)
                ->whereRaw('LOWER(TRIM(task_name)) = ?', [$normalized])
                ->value('id');

            if (!$masterId) {
                $masterId = DB::table('tbl_tasks')->insertGetId([
                    'task_name' => $name,
                    'task_code' => null,
                    'description' => null,
                    'status' => 1,
                    'created_by' => $userId,
                    'created_on' => $now,
                    'updated_by' => $userId,
                    'updated_on' => $now,
                    'is_delete' => 0,
                ]);
            }

            DB::table('tbl_project_department_tasks')
                ->where('id', $row->id)
                ->update(['task_id' => (int) $masterId]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('tbl_project_department_tasks') && Schema::hasColumn('tbl_project_department_tasks', 'task_id')) {
            Schema::table('tbl_project_department_tasks', function (Blueprint $table) {
                $table->dropIndex(['task_id']);
                $table->dropColumn('task_id');
            });
        }
    }
}
