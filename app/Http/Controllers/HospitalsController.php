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

class HospitalsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'hospitals';

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function hospital_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Hospital' : 'Create Hospital';
        $data = ['hospital' => ''];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_hospitals',
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
                    $record['hospital_id'] = $record['id'];
                    $data['hospital'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Hospital edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('hospitals.create-hospital-form', compact('pageTitle', 'data'));
        }

        return view('hospitals.create-hospital', compact('pageTitle', 'data'));
    }

    public function insert_update_hospital(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateHospitalData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $hospitalId = $postData['hospital_id'] ?? null;
            $operation = ($hospitalId && $hospitalId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareHospitalData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_hospitals', $payload);
                if ($newId) {
                    $this->auditTrail->log('hospital', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Hospital added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_hospitals', '*', ['id' => $hospitalId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_hospitals', $payload, 'id', $hospitalId);
                if ($result) {
                    $this->syncProjectHospitalNames((int) $hospitalId, $payload['hospital_name']);
                    $this->auditTrail->log('hospital', (int) $hospitalId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Hospital updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Hospital Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function hospital_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Hospitals';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Code', 'Hospital Name', 'Description', 'Status', 'Created On'],
            'table' => 'tbl_hospitals',
            'dataurl' => 'get_hospital_list',
            'addurl' => 'hospitals/add',
            'addurllabel' => 'Add Hospital',
            'filters' => [
                $this->buildTextFilter('search', 'Search hospital', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_hospital_list(Request $request)
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
                $searchColumns = ['tb.hospital_code', 'tb.hospital_name', 'tb.description'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*'],
                ['', '', 'tb.hospital_code', 'tb.hospital_name', 'tb.description', 'tb.status', 'tb.created_on'],
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
                $editUrl = getProjectUrl('hospitals/edit/' . $id);
                $desc = $recordData->description ?? '';
                if (strlen($desc) > 80) {
                    $desc = substr($desc, 0, 80) . '…';
                }

                $recordListing[] = [
                    $this->wrapGridActions('<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Hospital\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>'),
                    $srNumber + 1,
                    e($recordData->hospital_code),
                    e($recordData->hospital_name),
                    e($desc),
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
            Log::error('Get Hospital List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateHospitalData(array $postData): string
    {
        $errMessage = '';
        $code = trim($postData['hospital_code'] ?? '');
        $name = trim($postData['hospital_name'] ?? '');

        if ($code === '') {
            $errMessage .= '<li>Please enter hospital code</li>';
        }
        if ($name === '') {
            $errMessage .= '<li>Please enter hospital name</li>';
        }

        $hospitalId = $postData['hospital_id'] ?? null;
        if ($code !== '') {
            $query = DB::table('tbl_hospitals')->where('hospital_code', $code)->where('is_delete', 0);
            if ($hospitalId) {
                $query->where('id', '!=', $hospitalId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Hospital code already exists</li>';
            }
        }
        if ($name !== '') {
            $query = DB::table('tbl_hospitals')->where('hospital_name', $name)->where('is_delete', 0);
            if ($hospitalId) {
                $query->where('id', '!=', $hospitalId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Hospital name already exists</li>';
            }
        }

        return $errMessage;
    }

    private function prepareHospitalData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $data = [
            'hospital_code' => trim($postData['hospital_code']),
            'hospital_name' => trim($postData['hospital_name']),
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

    private function syncProjectHospitalNames(int $hospitalId, string $hospitalName): void
    {
        if (!Schema::hasTable('tbl_projects') || !Schema::hasColumn('tbl_projects', 'hospital_id')) {
            return;
        }

        DB::table('tbl_projects')
            ->where('hospital_id', $hospitalId)
            ->where('is_delete', 0)
            ->update(['hospital_name' => $hospitalName]);
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
