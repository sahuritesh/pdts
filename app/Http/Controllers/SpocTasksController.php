<?php

namespace App\Http\Controllers;

use App\Models\Datatables_model;
use App\Http\Traits\GridConfigTrait;
use App\Services\UserScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SpocTasksController extends Controller
{
    use GridConfigTrait;

    public $module = 'spoc_tasks';

    protected UserScopeService $userScope;

    public function __construct(UserScopeService $userScope)
    {
        $this->userScope = $userScope;
    }

    private function canAccess(): bool
    {
        return modulePermissionExists($this->module)
            || permissionexists('spoc_department_access') === '1';
    }

    public function task_list(Request $request)
    {
        if (!$this->canAccess()) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'My Department Tasks';
        $statusOptions = [
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'start', 'label' => 'Ready'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'delay', 'label' => 'Delayed'],
            ['value' => 'completed', 'label' => 'Completed'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project', 'Hospital', 'Zone', 'Location', 'Department', 'Status', 'Delay Days', 'Planned End', 'Actions'],
            'table' => 'tbl_project_departments',
            'dataurl' => 'get_spoc_task_list',
            'filters' => [
                $this->buildTextFilter('search', 'Search project or hospital', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_spoc_task_list(Request $request)
    {
        if (!$this->canAccess()) {
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        try {
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $deptTable = Schema::hasTable('tbl_departments') ? 'tbl_departments' : 'tbl_delay_categories';
            $nameCol = Schema::hasColumn($deptTable, 'department_name') ? 'department_name' : 'category_name';

            $wherecondition = [
                ['column' => 'pd.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
                ['column' => 'tp.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];

            if (!empty($status) && $status !== 'All') {
                $wherecondition[] = ['column' => 'pd.department_status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tp.project_code', 'tp.project_name', 'tp.hospital_name', "d.$nameCol"];
                $search_param = $search;
            }

            $joins = [
                ['table_name' => 'tbl_projects as tp', 'condition' => 'tp.id=pd.project_id', 'join_type' => 'inner'],
                ['table_name' => "$deptTable as d", 'condition' => 'd.id=pd.department_id', 'join_type' => 'left'],
                ['table_name' => 'tbl_zones as tz', 'condition' => 'tz.id=tp.zone_id', 'join_type' => 'left'],
            ];

            if (Schema::hasTable('tbl_locations')) {
                $joins[] = ['table_name' => 'tbl_locations as tl', 'condition' => 'tl.id=tp.location_id', 'join_type' => 'left'];
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['pd.*', 'tp.project_code', 'tp.project_name', 'tp.hospital_name', 'tz.zone_name', 'tl.location_name', "d.$nameCol as department_name"],
                ['', 'tp.project_code', 'tp.hospital_name', 'tz.zone_name', 'tl.location_name', "d.$nameCol", 'pd.department_status', 'pd.delay_days', 'pd.planned_end_date'],
                'tbl_project_departments as pd',
                $joins,
                $wherecondition,
                'pd.id',
                $searchColumns,
                $search_param,
                'pd.id',
                'DESC',
                ''
            );

            $scopedIds = null;
            if ($this->userScope->isScopedUser()) {
                $scopedQuery = DB::table('tbl_project_departments as pd')
                    ->join('tbl_projects as tp', 'tp.id', '=', 'pd.project_id')
                    ->where('pd.is_delete', 0)
                    ->where('tp.is_delete', 0);
                $this->userScope->applyProjectDepartmentScope($scopedQuery, 'pd');
                $this->userScope->applyProjectScope($scopedQuery, 'tp');
                $scopedIds = $scopedQuery->pluck('pd.id')->map(fn ($id) => (int) $id)->all();
            }

            $recordsFiltered = 0;
            $recordListing = [];
            $srNumber = $start;

            foreach ($getRecordListing['data'] as $recordData) {
                if ($scopedIds !== null && !in_array((int) $recordData->id, $scopedIds, true)) {
                    continue;
                }

                $recordsFiltered++;
                $projectId = Crypt::encrypt($recordData->project_id);
                $wizardUrl = getProjectUrl('projects/wizard/' . $projectId);

                $recordListing[] = [
                    $srNumber + 1,
                    e(trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''))),
                    e($recordData->hospital_name ?? ''),
                    e($recordData->zone_name ?? ''),
                    e($recordData->location_name ?? ''),
                    e($recordData->department_name ?? ''),
                    $this->formatStatusBadge($recordData->department_status),
                    (int) ($recordData->delay_days ?? 0),
                    !empty($recordData->planned_end_date) ? displayCustomDateTime($recordData->planned_end_date) : '',
                    '<a href="' . $wizardUrl . '" title="Open project"><i class="ri-external-link-line"></i></a>',
                ];
                $srNumber++;
            }

            return response()->json([
                'draw' => (int) $draw,
                'recordsTotal' => $recordsFiltered,
                'recordsFiltered' => $recordsFiltered,
                'data' => $recordListing,
            ]);
        } catch (\Exception $e) {
            Log::error('Get SPOC Task List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function formatStatusBadge(?string $status): string
    {
        $map = [
            'pending' => ['Pending', 'badge-soft-secondary'],
            'start' => ['Ready', 'badge-soft-info'],
            'in_progress' => ['In Progress', 'badge-soft-primary'],
            'delay' => ['Delayed', 'badge-soft-warning'],
            'completed' => ['Completed', 'badge-soft-success'],
        ];
        $info = $map[$status] ?? [ucfirst((string) $status), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }
}
