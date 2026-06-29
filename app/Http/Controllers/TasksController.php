<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use App\Services\TaskMasterService;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TasksController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'tasks';

    protected AuditTrailService $auditTrail;
    protected TaskMasterService $taskMasterService;

    public function __construct(AuditTrailService $auditTrail, TaskMasterService $taskMasterService)
    {
        $this->auditTrail = $auditTrail;
        $this->taskMasterService = $taskMasterService;
    }

    public function task_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Task' : 'Create Task';
        $data = ['task' => ''];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $task = $this->taskMasterService->resolveTask((int) $decryptedId);
                if ($task) {
                    $task['task_id'] = $task['id'];
                    $data['task'] = $task;
                }
            } catch (\Exception $e) {
                Log::error('Task edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('tasks.create-task-form', compact('pageTitle', 'data'));
        }

        return view('tasks.create-task', compact('pageTitle', 'data'));
    }

    public function insert_update_task(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $taskId = $postData['task_id'] ?? null;
            $operation = ($taskId && $taskId !== '') ? 'Update' : 'Add';
            $result = $this->taskMasterService->saveTask($postData);

            if (!empty($result['error'])) {
                $this->sendValidationErrorResponse('<li>' . e($result['msg'] ?? 'Save failed') . '</li>');
                return;
            }

            $this->sendSuccessResponse($result['msg'] ?? 'Task saved', $operation);
        } catch (\Exception $e) {
            Log::error('Task Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function task_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Tasks';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Task Name', 'Task Code', 'Status', 'Created On'],
            'table' => 'tbl_tasks',
            'dataurl' => 'get_task_list',
            'addurl' => 'tasks/add',
            'addurllabel' => 'Add Task',
            'filters' => [
                $this->buildTextFilter('search', 'Search task', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_task_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($status) && $status !== 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.task_name', 'tb.task_code', 'tb.description'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*'],
                ['', '', 'tb.task_name', 'tb.task_code', 'tb.status', 'tb.created_on'],
                "$table as tb",
                [],
                $wherecondition,
                'tb.id',
                $searchColumns,
                $search_param,
                'tb.id',
                'DESC',
                ''
            );

            $recordsFiltered = $getRecordListing['recordsFiltered'];
            $recordListing = [];
            $srNumber = $start;

            foreach ($getRecordListing['data'] as $recordData) {
                $id = Crypt::encrypt($recordData->id);
                $editUrl = getProjectUrl('tasks/edit/' . $id);
                $code = $recordData->task_code ?? '';

                $recordListing[] = [
                    $this->wrapGridActions('<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Task\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>'),
                    $srNumber + 1,
                    e($recordData->task_name ?? ''),
                    e($code !== '' ? $code : '—'),
                    $this->formatStatusBadge($recordData->status),
                    displayCustomDateTime($recordData->created_on),
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
            Log::error('Get Task List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function search_tasks(Request $request)
    {
        $term = trim((string) $request->input('q', $request->input('term', '')));
        $includeId = (int) $request->input('include_id', 0);
        $tasks = $this->taskMasterService->searchTasks($term, 20, $includeId ?: null);

        $results = array_map(function ($task) {
            $label = $task['task_name'];
            if (!empty($task['task_code'])) {
                $label .= ' (' . $task['task_code'] . ')';
            }

            return ['id' => $task['id'], 'text' => $label];
        }, $tasks);

        echo json_encode(['error' => 0, 'results' => $results]);
    }

    private function formatStatusBadge($status)
    {
        $statusName = ($status == ACTIVE) ? 'Active' : 'Inactive';
        $class = ($status == ACTIVE)
            ? 'badge rounded-pill badge-soft-success'
            : 'badge rounded-pill badge-soft-danger';

        return "<label class='$class'>$statusName</label>";
    }
}
