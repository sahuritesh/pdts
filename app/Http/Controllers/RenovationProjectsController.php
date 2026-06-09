<?php

namespace App\Http\Controllers;

use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenovationProjectsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = ['renovation_projects_create', 'renovation_projects_list'];

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function renovation_project_form(Request $request, $id = '')
    {
        if (permissionexists($this->module[0]) != '1') {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Renovation Project' : 'Create Renovation Project';
        $data = [
            'project' => '',
            'renovation_types' => $this->getRenovationTypes(),
            'project_statuses' => $this->getProjectStatuses(),
            'escalation_statuses' => $this->getEscalationStatuses(),
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_renovation_projects',
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
                    $record['renovation_project_id'] = $record['id'];
                    $data['project'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Renovation project edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('renovation_projects.create-renovation-project-form', compact('pageTitle', 'data'));
        }

        return view('renovation_projects.create-renovation-project', compact('pageTitle', 'data'));
    }

    public function insert_update_renovation_project(Request $request)
    {
        if (permissionexists($this->module[0]) != '1') {
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

            $projectId = $postData['renovation_project_id'] ?? null;
            $operation = ($projectId && $projectId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareProjectData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_renovation_projects', $payload);
                if ($newId) {
                    $this->auditTrail->log('renovation_project', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Renovation project added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_renovation_projects', '*', ['id' => $projectId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_renovation_projects', $payload, 'id', $projectId);
                if ($result) {
                    $this->auditTrail->log('renovation_project', (int) $projectId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Renovation project updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Renovation Project Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function renovation_project_list(Request $request)
    {
        if (permissionexists($this->module[1]) != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Renovation Projects';
        $statusOptions = array_map(fn ($s) => ['value' => $s['value'], 'label' => $s['label']], $this->getProjectStatuses());
        $typeOptions = array_map(fn ($t) => ['value' => $t['value'], 'label' => $t['label']], $this->getRenovationTypes());

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project ID', 'Project Name', 'Zone / Dept', 'Type', 'Location', 'Status', 'Handover', 'Escalation', 'Actions'],
            'table' => 'tbl_renovation_projects',
            'dataurl' => 'get_renovation_project_list',
            'addurl' => 'renovation-projects/add',
            'addurllabel' => 'Add Renovation Project',
            'filters' => [
                $this->buildTextFilter('search', 'Search project, zone, location', 'Search', 'col-md-3'),
                $this->buildSelectFilter('renovation_type', $typeOptions, 'Type', 'All types', true, true, 'col-md-2'),
                $this->buildSelectFilter('project_status', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_renovation_project_list(Request $request)
    {
        if (permissionexists($this->module[1]) != '1') {
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $renovationType = $filters['renovation_type'] ?? '';
            $projectStatus = $filters['project_status'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($renovationType) && $renovationType !== 'All') {
                $wherecondition[] = ['column' => 'tb.renovation_type', 'operator' => '', 'value' => $renovationType, 'condition' => 'and'];
            }
            if (!empty($projectStatus) && $projectStatus !== 'All') {
                $wherecondition[] = ['column' => 'tb.project_status', 'operator' => '', 'value' => $projectStatus, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = [
                    'tb.project_code', 'tb.project_name', 'tb.zone_department_impacted',
                    'tb.renovation_type', 'tb.location', 'tb.remarks',
                ];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*'],
                ['', 'tb.project_code', 'tb.project_name', 'tb.zone_department_impacted', 'tb.renovation_type', 'tb.location', 'tb.project_status', 'tb.final_handover_date', 'tb.escalation_status'],
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
                $editUrl = getProjectUrl('renovation-projects/edit/' . $id);

                $recordListing[] = [
                    $srNumber + 1,
                    e($recordData->project_code),
                    e($recordData->project_name),
                    e($recordData->zone_department_impacted ?? ''),
                    e($recordData->renovation_type ?? ''),
                    e($recordData->location ?? ''),
                    $this->formatProjectStatusBadge($recordData->project_status),
                    !empty($recordData->final_handover_date) ? displayCustomDateTime($recordData->final_handover_date) : '',
                    $this->formatEscalationBadge($recordData->escalation_status),
                    '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Renovation Project\', 90); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
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
            Log::error('Get Renovation Project List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function getRenovationTypes(): array
    {
        return [
            ['value' => 'Department Renovation', 'label' => 'Department Renovation'],
            ['value' => 'Department Upgrade', 'label' => 'Department Upgrade'],
            ['value' => 'Facility Upgrade', 'label' => 'Facility Upgrade'],
            ['value' => 'Infrastructure Renovation', 'label' => 'Infrastructure Renovation'],
            ['value' => 'Infection Control Retrofit', 'label' => 'Infection Control Retrofit'],
            ['value' => 'Other', 'label' => 'Other'],
        ];
    }

    private function getProjectStatuses(): array
    {
        return [
            ['value' => 'planned', 'label' => 'Planned'],
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'delayed', 'label' => 'Delayed'],
            ['value' => 'completed', 'label' => 'Completed'],
            ['value' => 'on_hold', 'label' => 'On Hold'],
        ];
    }

    private function getEscalationStatuses(): array
    {
        return [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'resolved', 'label' => 'Resolved'],
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

        $projectId = $postData['renovation_project_id'] ?? null;
        if ($code !== '') {
            $query = DB::table('tbl_renovation_projects')->where('project_code', $code)->where('is_delete', 0);
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

        $data = [
            'project_code' => trim($postData['project_code']),
            'project_name' => trim($postData['project_name']),
            'project_scope' => trim($postData['project_scope'] ?? ''),
            'location' => trim($postData['location'] ?? ''),
            'zone_department_impacted' => trim($postData['zone_department_impacted'] ?? ''),
            'renovation_type' => trim($postData['renovation_type'] ?? ''),
            'project_status' => $postData['project_status'] ?? 'planned',
            'final_handover_date' => $this->nullableDate($postData['final_handover_date'] ?? ''),
            'escalation_status' => $postData['escalation_status'] ?? 'none',
            'remarks' => trim($postData['remarks'] ?? ''),
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

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function formatProjectStatusBadge(?string $status): string
    {
        $map = [
            'planned' => ['Planned', 'badge-soft-secondary'],
            'active' => ['Active', 'badge-soft-success'],
            'in_progress' => ['In Progress', 'badge-soft-info'],
            'delayed' => ['Delayed', 'badge-soft-warning'],
            'completed' => ['Completed', 'badge-soft-primary'],
            'on_hold' => ['On Hold', 'badge-soft-dark'],
        ];
        $info = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }

    private function formatEscalationBadge(?string $status): string
    {
        $map = [
            'none' => ['None', 'badge-soft-secondary'],
            'escalated' => ['Escalated', 'badge-soft-danger'],
            'resolved' => ['Resolved', 'badge-soft-success'],
        ];
        $info = $map[$status] ?? [ucfirst((string) $status), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }
}
