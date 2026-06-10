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

class ProjectsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'projects';

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function project_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Project' : 'Create Project';
        $data = [
            'project' => '',
            'project_types' => $this->getProjectTypes(),
            'zones' => $this->getZones(),
            'project_statuses' => $this->getProjectStatuses(),
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_projects',
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
                    $record['project_id'] = $record['id'];
                    $data['project'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Project edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('projects.create-project-form', compact('pageTitle', 'data'));
        }

        return view('projects.create-project', compact('pageTitle', 'data'));
    }

    public function insert_update_project(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateProjectData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $projectId = $postData['project_id'] ?? null;
            $operation = ($projectId && $projectId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareProjectData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_projects', $payload);
                if ($newId) {
                    $this->auditTrail->log('project', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Project added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_projects', '*', ['id' => $projectId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_projects', $payload, 'id', $projectId);
                if ($result) {
                    $this->auditTrail->log('project', (int) $projectId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Project updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Project Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function project_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Construction / Delay Projects';
        $statusOptions = array_map(function ($s) {
            return ['value' => $s['value'], 'label' => $s['label']];
        }, $this->getProjectStatuses());

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project ID', 'Project Name', 'Hospital', 'Type', 'Zone', 'Area', 'SPOC', 'Status', 'Planned End', 'Actions'],
            'table' => 'tbl_projects',
            'dataurl' => 'get_project_list',
            'addurl' => 'projects/add',
            'addurllabel' => 'Add Project',
            'filters' => [
                $this->buildTextFilter('search', 'Search project, hospital, contractor', 'Search', 'col-md-3'),
                $this->buildTextFilter('hospital', 'Hospital name', 'Hospital', 'col-md-2'),
                $this->buildSelectFilter('project_status', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_project_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $hospital = $filters['hospital'] ?? '';
            $projectStatus = $filters['project_status'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($projectStatus) && $projectStatus !== 'All') {
                $wherecondition[] = ['column' => 'tb.project_status', 'operator' => '', 'value' => $projectStatus, 'condition' => 'and'];
            }
            if (!empty($hospital)) {
                $wherecondition[] = ['column' => 'tb.hospital_name', 'operator' => 'LIKE', 'value' => '%' . $hospital . '%', 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.project_code', 'tb.project_name', 'tb.hospital_name', 'tb.contractor_name', 'tb.project_spoc_name'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'tz.zone_name'],
                ['', 'tb.project_code', 'tb.project_name', 'tb.hospital_name', 'tb.project_type_label', 'tz.zone_name', 'tb.area_facility', 'tb.project_spoc_name', 'tb.project_status', 'tb.planned_completion_date'],
                "$table as tb",
                [
                    ['table_name' => 'tbl_zones as tz', 'condition' => 'tz.id=tb.zone_id', 'join_type' => 'left'],
                ],
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
                $editUrl = getProjectUrl('projects/edit/' . $id);

                $recordListing[] = [
                    $srNumber + 1,
                    e($recordData->project_code),
                    e($recordData->project_name),
                    e($recordData->hospital_name ?? ''),
                    e($recordData->project_type_label ?? ''),
                    e($recordData->zone_name ?? $recordData->zone_department ?? ''),
                    e($recordData->area_facility ?? ''),
                    e($recordData->project_spoc_name ?? $recordData->responsibility_name ?? ''),
                    $this->formatProjectStatusBadge($recordData->project_status),
                    !empty($recordData->planned_completion_date) ? displayCustomDateTime($recordData->planned_completion_date) : '',
                    '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Project\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
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
            Log::error('Get Project List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function getProjectTypes(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_project_types')) {
            return [];
        }
        return DB::table('tbl_project_types')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('type_name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->type_name, 'code' => $r->type_code])
            ->all();
    }

    private function getZones(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_zones')) {
            return [];
        }
        return DB::table('tbl_zones')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('zone_name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->zone_name, 'code' => $r->zone_code])
            ->all();
    }

    private function getProjectStatuses(): array
    {
        return [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'delayed', 'label' => 'Delayed'],
            ['value' => 'completed', 'label' => 'Completed'],
            ['value' => 'on_hold', 'label' => 'On Hold'],
        ];
    }

    private function validateProjectData(array $postData): string
    {
        $errMessage = '';
        $code = trim($postData['project_code'] ?? '');
        $name = trim($postData['project_name'] ?? '');

        if ($code === '') {
            $errMessage .= '<li>Please enter project ID / code</li>';
        }
        if ($name === '') {
            $errMessage .= '<li>Please enter project name</li>';
        }

        $projectId = $postData['project_id'] ?? null;
        if ($code !== '') {
            $query = DB::table('tbl_projects')->where('project_code', $code)->where('is_delete', 0);
            if ($projectId) {
                $query->where('id', '!=', $projectId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Project ID already exists</li>';
            }
        }

        return $errMessage;
    }

    private function prepareProjectData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $typeId = !empty($postData['project_type_id']) ? (int) $postData['project_type_id'] : null;
        $typeLabel = null;
        if ($typeId) {
            $typeLabel = DB::table('tbl_project_types')->where('id', $typeId)->value('type_name');
        }

        $zoneId = !empty($postData['zone_id']) ? (int) $postData['zone_id'] : null;
        $zoneName = null;
        if ($zoneId && DB::getSchemaBuilder()->hasTable('tbl_zones')) {
            $zoneName = DB::table('tbl_zones')->where('id', $zoneId)->value('zone_name');
        }

        $spoc = trim($postData['project_spoc_name'] ?? '');

        $data = [
            'project_code' => trim($postData['project_code']),
            'project_name' => trim($postData['project_name']),
            'project_scope' => trim($postData['project_scope'] ?? ''),
            'location' => trim($postData['location'] ?? ''),
            'hospital_name' => trim($postData['hospital_name'] ?? ''),
            'contractor_name' => trim($postData['contractor_name'] ?? ''),
            'zone_id' => $zoneId,
            'zone_department' => $zoneName ?: trim($postData['zone_department'] ?? ''),
            'area_facility' => trim($postData['area_facility'] ?? ''),
            'project_type_id' => $typeId,
            'project_type_label' => $typeLabel ?: trim($postData['project_type_label'] ?? ''),
            'project_spoc_name' => $spoc,
            'responsibility_name' => $spoc,
            'responsible_user_id' => !empty($postData['responsible_user_id']) ? (int) $postData['responsible_user_id'] : null,
            'planned_start_date' => $this->nullableDate($postData['planned_start_date'] ?? ''),
            'planned_completion_date' => $this->nullableDate($postData['planned_completion_date'] ?? ''),
            'actual_completion_date' => $this->nullableDate($postData['actual_completion_date'] ?? ''),
            'target_revised_completion_date' => $this->nullableDate($postData['target_revised_completion_date'] ?? ''),
            'project_status' => $postData['project_status'] ?? 'active',
            'updated_by' => $userId,
            'updated_on' => current_datetime(),
        ];

        if ($operation === 'Add') {
            $data['total_delay_cost'] = 0;
            $data['created_by'] = $userId;
            $data['created_on'] = current_datetime();
            $data['is_delete'] = 0;
        }

        return $data;
    }

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function formatProjectStatusBadge(?string $status): string
    {
        $map = [
            'active' => ['Active', 'badge-soft-success'],
            'delayed' => ['Delayed', 'badge-soft-warning'],
            'completed' => ['Completed', 'badge-soft-info'],
            'on_hold' => ['On Hold', 'badge-soft-secondary'],
        ];
        $info = $map[$status] ?? [ucfirst((string) $status), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }
}
