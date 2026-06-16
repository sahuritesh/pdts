<?php

namespace App\Http\Controllers;

use App\Models\Datatables_model;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Services\ProjectDepartmentService;
use App\Services\UserScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SpocTasksController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'spoc_tasks';

    protected UserScopeService $userScope;
    protected ProjectDepartmentService $projectDepartmentService;

    public function __construct(UserScopeService $userScope, ProjectDepartmentService $projectDepartmentService)
    {
        $this->userScope = $userScope;
        $this->projectDepartmentService = $projectDepartmentService;
    }

    private function canAccess(): bool
    {
        return modulePermissionExists($this->module)
            || $this->userScope->hasMyDepartmentTasksAccess();
    }

    public function task_list(Request $request)
    {
        if (!$this->canAccess()) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'My Department Tasks';
        $statusOptions = $this->projectDepartmentService->statusFilterOptions();

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Project', 'Hospital', 'Zone', 'Location', 'Department', 'Status', 'Delay Days', 'Planned End'],
            'table' => 'tbl_project_departments',
            'dataurl' => 'get_spoc_task_list',
            'no_sort_columns' => ['Actions'],
            'filters' => [
                $this->buildTextFilter('search', 'Search project or hospital', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function task_detail(Request $request, $id = '')
    {
        if (!$this->canAccess()) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        try {
            $pdId = (int) Crypt::decrypt($id);
        } catch (\Exception $e) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'Invalid task']);
            }
            return redirect(getProjectUrl('spoc-tasks-list'))->with('error', 'Invalid task');
        }

        if (!$this->userScope->canAccessProjectDepartment($pdId)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You do not have access to this department task']);
            }
            return redirect(getProjectUrl('spoc-tasks-list'))->with('error', 'You do not have access to this department task');
        }

        $this->claimTaskIfUnassigned($pdId);

        $detail = $this->projectDepartmentService->resolveDepartment($pdId, true);
        if (!$detail) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'Task not found']);
            }
            return redirect(getProjectUrl('spoc-tasks-list'))->with('error', 'Task not found');
        }

        $pageTitle = 'My Task — ' . ($detail['department']['department_name'] ?? 'Department');
        $data = array_merge($detail, [
            'status_labels' => $this->projectDepartmentService->statusLabels(),
        ]);

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('spoc_tasks.task-detail', compact('pageTitle', 'data'));
        }

        return redirect(getProjectUrl('spoc-tasks-list'));
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
                ['', '', 'tp.project_code', 'tp.hospital_name', 'tz.zone_name', 'tl.location_name', "d.$nameCol", 'pd.department_status', 'pd.delay_days', 'pd.planned_end_date'],
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
                $encPdId = Crypt::encrypt($recordData->id);
                $deptName = $recordData->department_name ?? 'Department';
                $status = $recordData->department_status ?? 'pending';
                $isPending = $status === 'pending';

                $row = [
                    $this->buildSpocTaskActions($encPdId, $deptName, $isPending),
                    $srNumber + 1,
                    e(trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''))),
                    e($recordData->hospital_name ?? ''),
                    e($recordData->zone_name ?? ''),
                    e($recordData->location_name ?? ''),
                    e($deptName),
                    $this->projectDepartmentService->statusBadgeHtml($status),
                    (int) ($recordData->delay_days ?? 0),
                    !empty($recordData->planned_end_date) ? displayCustomDateTime($recordData->planned_end_date) : '',
                ];

                $recordListing[] = $row;
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

    private function buildSpocTaskActions(string $encPdId, string $deptName, bool $isPending): string
    {
        $actions = [
            $this->buildSideLayoutLink(
                getProjectUrl('spoc-tasks/view/' . $encPdId),
                'Manage Task',
                'ri-edit-box-line',
                'Manage task'
            ),
        ];

        if (!$isPending) {
            foreach ($this->projectDepartmentService->workflowPanels() as $type => $panel) {
                $actions[] = $this->buildSideLayoutLink(
                    $this->projectDepartmentService->panelUrl($type, $encPdId),
                    $this->projectDepartmentService->panelTitle($type, $deptName),
                    $panel['icon'],
                    $panel['label']
                );
            }
        }

        return $this->wrapGridActions(implode('', $actions));
    }

    private function claimTaskIfUnassigned(int $pdId): void
    {
        if (!$this->userScope->isScopedUser()) {
            return;
        }

        if ($this->userScope->hasAssignedProjectsAsResponsible() && empty($this->userScope->getAssignedDepartmentIds())) {
            return;
        }

        $row = DB::table('tbl_project_departments')->where('id', $pdId)->where('is_delete', 0)->first();
        if (!$row || !empty($row->spoc_user_id)) {
            return;
        }

        $user = Auth::user();
        $this->projectDepartmentService->updateDepartmentRow($pdId, [
            'spoc_user_id' => Auth::id(),
            'spoc_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        ]);

        $deptIds = $this->userScope->getAssignedDepartmentIds();
        if (!empty($deptIds) || (int) $row->department_id > 0) {
            $merged = array_values(array_unique(array_merge($deptIds, [(int) $row->department_id])));
            $this->userScope->syncUserDepartments((int) Auth::id(), $merged);
        }
    }
}
