<?php

namespace App\Http\Controllers;

use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use App\Services\DelayRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelayRegistersController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'delay_registers';

    protected AuditTrailService $auditTrail;
    protected DelayRegisterService $delayRegisterService;

    public function __construct(AuditTrailService $auditTrail, DelayRegisterService $delayRegisterService)
    {
        $this->auditTrail = $auditTrail;
        $this->delayRegisterService = $delayRegisterService;
    }

    public function delay_register_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Delay Entry' : 'Log Delay';
        $data = [
            'register' => '',
            'projects' => $this->getActiveProjects(),
            'categories' => $this->getActiveCategories(),
            'root_causes' => $this->getRootCauses(),
            'register_statuses' => $this->getRegisterStatuses(),
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_delay_registers',
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
                    $record['delay_register_id'] = $record['id'];
                    $data['register'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Delay register edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_registers.create-delay-register-form', compact('pageTitle', 'data'));
        }

        return view('delay_registers.create-delay-register', compact('pageTitle', 'data'));
    }

    public function insert_update_delay_register(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateRegisterData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $registerId = $postData['delay_register_id'] ?? null;
            $operation = ($registerId && $registerId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareRegisterData($postData, $operation);
            $payload = $this->delayRegisterService->applyAutoCalculations($payload);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_delay_registers', $payload);
                if ($newId) {
                    $this->syncProjectDelayedStatus((int) $payload['project_id']);
                    $this->auditTrail->log('delay_register', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Delay entry logged successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_delay_registers', '*', ['id' => $registerId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_delay_registers', $payload, 'id', $registerId);
                if ($result) {
                    $this->syncProjectDelayedStatus((int) $payload['project_id']);
                    $this->auditTrail->log('delay_register', (int) $registerId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Delay entry updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Delay Register Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function delay_register_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Delay Register';
        $severityOptions = [
            ['value' => 'minor', 'label' => 'Minor'],
            ['value' => 'moderate', 'label' => 'Moderate'],
            ['value' => 'critical', 'label' => 'Critical'],
            ['value' => 'showstopper', 'label' => 'Showstopper'],
        ];
        $statusOptions = array_map(fn ($s) => ['value' => $s['value'], 'label' => $s['label']], $this->getRegisterStatuses());
        $categoryOptions = array_map(fn ($c) => ['value' => $c->id, 'label' => $c->category_name], $this->getActiveCategories());
        $hospitalOptions = $this->getHospitalFilterOptions();

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project', 'Delay Title', 'Category', 'Days', 'Severity', 'Alert', 'Escalation', 'Status', 'Start', 'End', 'Actions'],
            'table' => 'tbl_delay_registers',
            'dataurl' => 'get_delay_register_list',
            'addurl' => 'delay-registers/add',
            'addurllabel' => 'Log Delay',
            'filters' => [
                $this->buildTextFilter('search', 'Search title, task, responsibility', 'Search', 'col-md-3'),
                $this->buildSelectFilter('project_id', $this->getProjectFilterOptions(), 'Project', 'All projects', true, true, 'col-md-2'),
                $this->buildSelectFilter('delay_category_id', $categoryOptions, 'Category', 'All categories', true, true, 'col-md-2'),
                $this->buildSelectFilter('hospital_name', $hospitalOptions, 'Hospital', 'All hospitals', true, true, 'col-md-2'),
                $this->buildSelectFilter('severity', $severityOptions, 'Severity', 'All severities', true, true, 'col-md-2'),
                $this->buildSelectFilter('register_status', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_delay_register_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
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
            $projectId = $filters['project_id'] ?? '';
            $categoryId = $filters['delay_category_id'] ?? '';
            $hospitalName = $filters['hospital_name'] ?? '';
            $severity = $filters['severity'] ?? '';
            $registerStatus = $filters['register_status'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($projectId) && $projectId !== 'All') {
                $wherecondition[] = ['column' => 'tb.project_id', 'operator' => '', 'value' => $projectId, 'condition' => 'and'];
            }
            if (!empty($categoryId) && $categoryId !== 'All') {
                $wherecondition[] = ['column' => 'tb.delay_category_id', 'operator' => '', 'value' => $categoryId, 'condition' => 'and'];
            }
            if (!empty($hospitalName) && $hospitalName !== 'All') {
                $wherecondition[] = ['column' => 'tp.hospital_name', 'operator' => '', 'value' => $hospitalName, 'condition' => 'and'];
            }
            if (!empty($severity) && $severity !== 'All') {
                $wherecondition[] = ['column' => 'tb.severity', 'operator' => '', 'value' => $severity, 'condition' => 'and'];
            }
            if (!empty($registerStatus) && $registerStatus !== 'All') {
                $wherecondition[] = ['column' => 'tb.register_status', 'operator' => '', 'value' => $registerStatus, 'condition' => 'and'];
            }

            $joinsArray = [
                ['table_name' => 'tbl_projects as tp', 'condition' => 'tp.id=tb.project_id', 'join_type' => 'left'],
                ['table_name' => 'tbl_delay_categories as tc', 'condition' => 'tc.id=tb.delay_category_id', 'join_type' => 'left'],
            ];

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = [
                    'tb.delay_title', 'tb.specific_event_description', 'tb.impacted_task',
                    'tb.responsibility_name', 'tp.project_code', 'tp.project_name', 'tp.hospital_name', 'tc.category_name',
                ];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'tp.project_code', 'tp.project_name', 'tp.hospital_name', 'tc.category_name'],
                ['', 'tp.project_code', 'tb.delay_title', 'tc.category_name', 'tb.delay_days', 'tb.severity', 'tb.alert_level', 'tb.escalation_level', 'tb.register_status', 'tb.delay_start_date', 'tb.delay_end_date'],
                "$table as tb",
                $joinsArray,
                $wherecondition,
                'tb.id',
                $searchColumns,
                $search_param,
                'tb.id',
                'DESC',
                '',
                '',
                1
            );

            $recordsFiltered = $getRecordListing['recordsFiltered'];
            $recordListing = [];
            $srNumber = $start;

            foreach ($getRecordListing['data'] as $recordData) {
                $id = Crypt::encrypt($recordData->id);
                $editUrl = getProjectUrl('delay-registers/edit/' . $id);
                $projectLabel = trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''));

                $recordListing[] = [
                    $srNumber + 1,
                    e($projectLabel),
                    e($recordData->delay_title ?? ''),
                    e($recordData->category_name ?? ''),
                    (int) ($recordData->delay_days ?? 0),
                    $this->formatSeverityBadge($recordData->severity),
                    $this->formatAlertBadge($recordData->alert_level),
                    $recordData->escalation_level ? 'L' . (int) $recordData->escalation_level : '—',
                    $this->formatRegisterStatusBadge($recordData->register_status),
                    !empty($recordData->delay_start_date) ? displayCustomDateTime($recordData->delay_start_date) : '',
                    !empty($recordData->delay_end_date) ? displayCustomDateTime($recordData->delay_end_date) : '',
                    $this->formatDelayRegisterActions($id),
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
            Log::error('Get Delay Register List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateRegisterData(array $postData): string
    {
        $errMessage = '';
        if (empty($postData['project_id'])) {
            $errMessage .= '<li>Please select a project</li>';
        }
        if (trim($postData['delay_title'] ?? '') === '') {
            $errMessage .= '<li>Please enter delay title / specific event</li>';
        }
        return $errMessage;
    }

    private function prepareRegisterData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $rootCauseId = !empty($postData['root_cause_id']) ? (int) $postData['root_cause_id'] : null;
        $rootCauseLabel = trim($postData['root_cause_label'] ?? '');
        if ($rootCauseId && $rootCauseLabel === '') {
            $rootCauseLabel = (string) DB::table('tbl_root_causes')->where('id', $rootCauseId)->value('cause_name');
        }

        $data = [
            'project_id' => (int) $postData['project_id'],
            'delay_title' => trim($postData['delay_title']),
            'delay_description' => trim($postData['delay_description'] ?? $postData['specific_event_description'] ?? ''),
            'primary_delay_drivers' => trim($postData['primary_delay_drivers'] ?? ''),
            'specific_event_description' => trim($postData['specific_event_description'] ?? ''),
            'impacted_task' => trim($postData['impacted_task'] ?? ''),
            'root_cause_id' => $rootCauseId,
            'root_cause_label' => $rootCauseLabel,
            'delay_category_id' => !empty($postData['delay_category_id']) ? (int) $postData['delay_category_id'] : null,
            'responsibility_user_id' => !empty($postData['responsibility_user_id']) ? (int) $postData['responsibility_user_id'] : null,
            'responsibility_name' => trim($postData['responsibility_name'] ?? ''),
            'delay_start_date' => $this->nullableDate($postData['delay_start_date'] ?? ''),
            'delay_end_date' => $this->nullableDate($postData['delay_end_date'] ?? ''),
            'target_revised_completion_date' => $this->nullableDate($postData['target_revised_completion_date'] ?? ''),
            'licensing_openings_affected' => !empty($postData['licensing_openings_affected']) ? 1 : 0,
            'register_status' => $postData['register_status'] ?? 'open',
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

    private function syncProjectDelayedStatus(int $projectId): void
    {
        if ($projectId <= 0) {
            return;
        }
        DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->where('project_status', '!=', 'completed')
            ->update([
                'project_status' => 'delayed',
                'updated_on' => current_datetime(),
                'updated_by' => Auth::id(),
            ]);
    }

    private function getActiveProjects(): array
    {
        return DB::table('tbl_projects')
            ->where('is_delete', 0)
            ->orderBy('project_name')
            ->get(['id', 'project_code', 'project_name', 'hospital_name'])
            ->map(fn ($r) => [
                'id' => $r->id,
                'label' => $r->project_code . ' — ' . $r->project_name . ($r->hospital_name ? ' (' . $r->hospital_name . ')' : ''),
            ])
            ->all();
    }

    private function getProjectFilterOptions(): array
    {
        return array_map(fn ($p) => ['value' => $p['id'], 'label' => $p['label']], $this->getActiveProjects());
    }

    private function getHospitalFilterOptions(): array
    {
        return DB::table('tbl_projects')
            ->where('is_delete', 0)
            ->whereNotNull('hospital_name')
            ->where('hospital_name', '!=', '')
            ->distinct()
            ->orderBy('hospital_name')
            ->pluck('hospital_name')
            ->map(fn ($name) => ['value' => $name, 'label' => $name])
            ->all();
    }

    private function getActiveCategories(): array
    {
        return DB::table('tbl_delay_categories')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('category_name')
            ->get(['id', 'category_name', 'description'])
            ->all();
    }

    private function getRootCauses(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_root_causes')) {
            return [];
        }
        return DB::table('tbl_root_causes')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('cause_name')
            ->get(['id', 'cause_name'])
            ->all();
    }

    private function getRegisterStatuses(): array
    {
        return [
            ['value' => 'open', 'label' => 'Open'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'closed', 'label' => 'Closed'],
        ];
    }

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function formatSeverityBadge(?string $severity): string
    {
        $map = [
            'minor' => ['Minor', 'badge-soft-success'],
            'moderate' => ['Moderate', 'badge-soft-warning'],
            'critical' => ['Critical', 'badge-soft-danger'],
            'showstopper' => ['Showstopper', 'badge-soft-dark'],
        ];
        $info = $map[$severity] ?? [ucfirst((string) $severity), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }

    private function formatAlertBadge(?string $level): string
    {
        $map = [
            'green' => ['Green', 'badge-soft-success'],
            'amber' => ['Amber', 'badge-soft-warning'],
            'red' => ['Red', 'badge-soft-danger'],
            'black' => ['Black', 'badge-soft-dark'],
        ];
        $info = $map[$level] ?? [ucfirst((string) $level), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }

    private function formatRegisterStatusBadge(?string $status): string
    {
        $map = [
            'open' => ['Open', 'badge-soft-warning'],
            'in_progress' => ['In Progress', 'badge-soft-info'],
            'closed' => ['Closed', 'badge-soft-success'],
        ];
        $info = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }

    private function formatDelayRegisterActions(string $encryptedId): string
    {
        $editUrl = getProjectUrl('delay-registers/edit/' . $encryptedId);
        $mitigationUrl = getProjectUrl('delay-mitigations/panel/' . $encryptedId);
        $mitigationListUrl = getProjectUrl('delay-mitigations-list/' . $encryptedId);

        $html = '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Delay Entry\', 90); return false;" title="Edit delay"><i class="ri-edit-fill"></i></a>';

        if (modulePermissionExists('mitigations')) {
            $html .= ' <a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $mitigationUrl . '\', \'Mitigations\', 95); return false;" title="Mitigations"><i class="ri-shield-check-line"></i></a>';
            $html .= ' <a href="' . $mitigationListUrl . '" title="Open mitigations list"><i class="ri-list-check-2"></i></a>';
        }

        if (modulePermissionExists('financial_impacts')) {
            $financialUrl = getProjectUrl('delay-financial-impacts/panel/' . $encryptedId);
            $financialListUrl = getProjectUrl('delay-financial-impacts-list/' . $encryptedId);
            $html .= ' <a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $financialUrl . '\', \'Financial Impact\', 95); return false;" title="Financial impact"><i class="ri-money-dollar-circle-line"></i></a>';
            $html .= ' <a href="' . $financialListUrl . '" title="Open financial impact list"><i class="ri-funds-line"></i></a>';
        }

        if (modulePermissionExists('delay_attachments')) {
            $attachmentUrl = getProjectUrl('delay-attachments/panel/' . $encryptedId);
            $attachmentListUrl = getProjectUrl('delay-attachments-list/' . $encryptedId);
            $html .= ' <a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $attachmentUrl . '\', \'Attachments\', 95); return false;" title="Attachments"><i class="ri-attachment-2"></i></a>';
            $html .= ' <a href="' . $attachmentListUrl . '" title="Open attachments list"><i class="ri-file-list-3-line"></i></a>';
        }

        return $html;
    }
}
