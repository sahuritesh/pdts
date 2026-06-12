<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DepartmentsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'departments';

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    private function tableName(): string
    {
        return Schema::hasTable('tbl_departments') ? 'tbl_departments' : 'tbl_delay_categories';
    }

    private function nameColumn(): string
    {
        return Schema::hasColumn($this->tableName(), 'department_name') ? 'department_name' : 'category_name';
    }

    public function department_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Department' : 'Create Department';
        $data = ['department' => ''];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    $this->tableName(),
                    '*',
                    ['id' => $decryptedId, 'is_delete' => 0],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );
                if (!empty($rows[0])) {
                    $record = $rows[0];
                    $record['department_id'] = $record['id'];
                    $nameCol = $this->nameColumn();
                    if (!isset($record['department_name']) && isset($record[$nameCol])) {
                        $record['department_name'] = $record[$nameCol];
                    }
                    $data['department'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Department edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('departments.create-department-form', compact('pageTitle', 'data'));
        }

        return view('departments.create-department', compact('pageTitle', 'data'));
    }

    public function insert_update_department(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateDepartmentData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $departmentId = $postData['department_id'] ?? null;
            $operation = ($departmentId && $departmentId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareDepartmentData($postData, $operation);
            $table = $this->tableName();

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable($table, $payload);
                if ($newId) {
                    $this->auditTrail->log('department', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Department added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable($table, '*', ['id' => $departmentId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable($table, $payload, 'id', $departmentId);
                if ($result) {
                    $this->auditTrail->log('department', (int) $departmentId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Department updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Department Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function department_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Departments';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Department Name', 'Description', 'Status', 'Created On', 'Actions'],
            'table' => $this->tableName(),
            'dataurl' => 'get_department_list',
            'addurl' => 'departments/add',
            'addurllabel' => 'Add Department',
            'filters' => [
                $this->buildTextFilter('search', 'Search department', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_department_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';
            $nameCol = $this->nameColumn();

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($status) && $status !== 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ["tb.$nameCol", 'tb.description'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*'],
                ['', "tb.$nameCol", 'tb.description', 'tb.status', 'tb.created_on'],
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
                $editUrl = getProjectUrl('departments/edit/' . $id);
                $name = $recordData->$nameCol ?? '';
                $desc = $recordData->description ?? '';
                if (strlen($desc) > 80) {
                    $desc = substr($desc, 0, 80) . '…';
                }

                $recordListing[] = [
                    $srNumber + 1,
                    e($name),
                    e($desc),
                    $this->formatStatusBadge($recordData->status),
                    displayCustomDateTime($recordData->created_on),
                    '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Department\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
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
            Log::error('Get Department List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateDepartmentData(array $postData): string
    {
        $errMessage = '';
        $name = trim($postData['department_name'] ?? '');
        if ($name === '') {
            $errMessage .= '<li>Please enter department name</li>';
        }

        $departmentId = $postData['department_id'] ?? null;
        $nameCol = $this->nameColumn();
        if ($name !== '') {
            $query = DB::table($this->tableName())
                ->where($nameCol, $name)
                ->where('is_delete', 0);
            if ($departmentId) {
                $query->where('id', '!=', $departmentId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Department name already exists</li>';
            }
        }

        return $errMessage;
    }

    private function prepareDepartmentData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $nameCol = $this->nameColumn();
        $data = [
            $nameCol => trim($postData['department_name']),
            'description' => trim($postData['description'] ?? ''),
            'status' => isset($postData['status']) ? (int) $postData['status'] : ACTIVE,
            'updated_by' => $userId,
            'updated_on' => current_datetime(),
        ];

        if ($operation === 'Add') {
            $data['created_by'] = $userId;
            $data['created_on'] = current_datetime();
            $data['is_delete'] = 0;
        }

        return $data;
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
