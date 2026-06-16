<?php

namespace App\Http\Controllers;

use App\Http\Traits\WebResponseTrait;
use App\Models\Common_model;
use App\Services\AuditTrailService;
use App\Services\DelayRegisterService;
use App\Services\FinancialImpactService;
use App\Services\ProjectDepartmentService;
use App\Services\UserScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProjectWizardController extends Controller
{
    use WebResponseTrait;

    public $module = 'projects';

    protected AuditTrailService $auditTrail;
    protected ProjectDepartmentService $projectDepartmentService;
    protected DelayRegisterService $delayRegisterService;
    protected FinancialImpactService $financialImpactService;
    protected UserScopeService $userScope;

    public function __construct(
        AuditTrailService $auditTrail,
        ProjectDepartmentService $projectDepartmentService,
        DelayRegisterService $delayRegisterService,
        FinancialImpactService $financialImpactService,
        UserScopeService $userScope
    ) {
        $this->auditTrail = $auditTrail;
        $this->projectDepartmentService = $projectDepartmentService;
        $this->delayRegisterService = $delayRegisterService;
        $this->financialImpactService = $financialImpactService;
        $this->userScope = $userScope;
    }

    private function canAccessModule(): bool
    {
        return modulePermissionExists($this->module)
            || $this->userScope->hasMyProjectsAccess();
    }

    private function canAccessProject(int $projectId): bool
    {
        if (modulePermissionExists($this->module)) {
            return true;
        }

        return $this->userScope->canAccessProject($projectId);
    }

    private function canManageDepartmentWorkflow(): bool
    {
        return modulePermissionExists($this->module)
            || $this->userScope->hasMyProjectsAccess();
    }

    private function assertDepartmentAccess(int $projectDepartmentId): bool
    {
        if (modulePermissionExists($this->module)) {
            return true;
        }

        return $this->userScope->canAccessProjectDepartment($projectDepartmentId);
    }

    public function wizard(Request $request, $id = 'new')
    {
        if (!$this->userScope->hasFullProjectsPermission() && !$this->userScope->hasMyProjectsAccess()) {
            if ($this->userScope->hasMyDepartmentTasksAccess()) {
                return redirect(getProjectUrl('spoc-tasks-list'))
                    ->with('error', 'Use My Department Tasks to manage your assigned work.');
            }

            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        if (!$this->canAccessModule()) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $projectId = null;
        $project = null;
        $step = 1;
        $scopedFallbackUrl = getProjectsListingUrl();

        if ($id !== 'new') {
            try {
                $projectId = (int) Crypt::decrypt($id);
                if (!$this->canAccessProject($projectId)) {
                    return redirect($scopedFallbackUrl)->with('error', 'You do not have access to this project');
                }
                if (!$this->userScope->canEditProject($projectId)) {
                    return redirect($scopedFallbackUrl)->with('error', 'You have view-only access to this project. Use My Department Tasks for your work.');
                }
                $project = DB::table('tbl_projects')->where('id', $projectId)->where('is_delete', 0)->first();
                if (!$project) {
                    return redirect(getProjectsListingUrl())->with('error', 'Project not found');
                }
                if ($request->query('step') === 'execution') {
                    $this->projectDepartmentService->ensureWizardExecutionStep($projectId);
                    $project = DB::table('tbl_projects')->where('id', $projectId)->where('is_delete', 0)->first();
                }
                $step = max(1, min(3, (int) ($project->wizard_step ?? 1)));
            } catch (\Exception $e) {
                return redirect(getProjectsListingUrl())->with('error', 'Invalid project');
            }
        }

        if ($id === 'new' && !$this->userScope->hasFullProjectsPermission()) {
            return redirect($scopedFallbackUrl)->with('error', 'You do not have permission to create projects');
        }

        $pageTitle = $project ? 'Project Wizard — ' . $project->project_code : 'New Project';
        $responsibleUserId = $project ? (int) ($project->responsible_user_id ?? 0) : 0;
        $data = [
            'project' => $project ? (array) $project : null,
            'project_id' => $projectId,
            'encrypted_project_id' => $projectId ? Crypt::encrypt($projectId) : '',
            'wizard_step' => $step,
            'project_types' => $this->getProjectTypes(),
            'zones' => $this->getZones(),
            'master_departments' => $this->getActiveDepartments(),
            'project_departments' => $projectId ? $this->projectDepartmentService->getProjectDepartments($projectId) : [],
            'status_labels' => $this->projectDepartmentService->statusLabels(),
            'spoc_users' => $this->getSpocUserOptions('Department SPOC'),
            'project_spoc_users' => $this->getSpocUserOptions('Project SPOC', $responsibleUserId ?: null),
        ];

        return view('project_wizard.wizard', compact('pageTitle', 'data'));
    }

    public function save_wizard_step1(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $postData = $this->parseWizardPost($request);

            $errMessage = $this->validateProjectData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $projectId = $postData['project_id'] ?? null;
            if ($projectId === '' || $projectId === '0') {
                $projectId = null;
            }
            $operation = ($projectId && $projectId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareProjectData($postData, $operation);
            if ($operation === 'Add') {
                $payload['wizard_step'] = 2;
            } else {
                $existingStep = (int) DB::table('tbl_projects')->where('id', $projectId)->value('wizard_step');
                $payload['wizard_step'] = max(2, $existingStep);
            }

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_projects', $payload);
                if (!$newId) {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                    return;
                }
                $this->auditTrail->log('project', (int) $newId, 'create', null, $payload);
                $this->sendSuccessResponse(
                    'General details saved.',
                    $operation,
                    getProjectUrl('projects/wizard/' . Crypt::encrypt($newId)),
                    ['primaryId' => (int) $newId]
                );
            } else {
                $old = Common_model::getDataFromTable('tbl_projects', '*', ['id' => $projectId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_projects', $payload, 'id', $projectId);
                if ($result) {
                    $this->auditTrail->log('project', (int) $projectId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('General details saved.', $operation, null, ['primaryId' => (int) $projectId]);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Wizard step 1 error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function save_wizard_departments(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $postData = $this->parseWizardPost($request);
            $projectId = (int) ($postData['project_id'] ?? 0);
            if ($projectId <= 0) {
                $this->sendValidationErrorResponse('<li>Invalid project</li>');
                return;
            }

            $ordered = $postData['department_order'] ?? [];
            if (!is_array($ordered)) {
                $ordered = array_filter(explode(',', (string) $ordered));
            }
            $ordered = array_values(array_filter(array_map('intval', $ordered)));
            if (empty($ordered)) {
                $this->sendValidationErrorResponse('<li>Select at least one department</li>');
                return;
            }

            $this->projectDepartmentService->syncProjectDepartments($projectId, $ordered);

            DB::table('tbl_projects')->where('id', $projectId)->update([
                'wizard_step' => 3,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);

            $this->sendSuccessResponse('Departments saved.', 'Update', null, ['primaryId' => $projectId]);
        } catch (\Exception $e) {
            Log::error('Wizard step 2 error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function save_wizard_finish(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $postData = $this->parseWizardPost($request);
            $projectId = (int) ($postData['project_id'] ?? 0);
            if ($projectId <= 0) {
                $this->sendValidationErrorResponse('<li>Invalid project</li>');
                return;
            }

            DB::table('tbl_projects')->where('id', $projectId)->where('is_delete', 0)->update([
                'wizard_step' => 3,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ]);

            $this->sendSuccessResponse('Project wizard completed.', 'Update', null, ['primaryId' => $projectId]);
        } catch (\Exception $e) {
            Log::error('Wizard finish error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function save_project_department(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $postData = $this->parseWizardPost($request);
            $pdId = (int) ($postData['project_department_id'] ?? 0);
            $row = DB::table('tbl_project_departments')->where('id', $pdId)->where('is_delete', 0)->first();
            if (!$row) {
                $this->sendErrorResponse('Department not found', 1);
                return;
            }

            if (!$this->assertDepartmentAccess($pdId)) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            $deptUpdate = [
                'planned_start_date' => $this->nullableDate($postData['planned_start_date'] ?? ''),
                'planned_end_date' => $this->nullableDate($postData['planned_end_date'] ?? ''),
                'remarks' => trim($postData['remarks'] ?? ''),
            ];

            if (!empty($postData['spoc_user_id'])) {
                $spocUserId = (int) $postData['spoc_user_id'];
                $spocUser = DB::table('tbl_user')->where('id', $spocUserId)->where('status', ACTIVE)->first();
                if ($spocUser) {
                    $deptUpdate['spoc_user_id'] = $spocUserId;
                    $deptUpdate['spoc_name'] = trim(($spocUser->first_name ?? '') . ' ' . ($spocUser->last_name ?? ''));
                    $this->assignUserToDepartment($spocUserId, (int) $row->department_id);
                }
            } elseif (!empty($postData['spoc_name'])) {
                $deptUpdate['spoc_name'] = trim($postData['spoc_name']);
            } elseif ($this->userScope->isScopedUser()) {
                $deptUpdate['spoc_user_id'] = Auth::id();
                $deptUpdate['spoc_name'] = trim((Auth::user()->first_name ?? '') . ' ' . (Auth::user()->last_name ?? ''));
            }

            $this->projectDepartmentService->updateDepartmentRow($pdId, $deptUpdate);
            $this->projectDepartmentService->ensureWizardExecutionStep((int) $row->project_id);

            $this->sendSuccessResponse('Department details saved', 'Update');
        } catch (\Exception $e) {
            Log::error('Save project department: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function update_department_status(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);
            $pdId = (int) ($postData['project_department_id'] ?? 0);
            $action = $postData['action'] ?? '';

            $row = DB::table('tbl_project_departments')->where('id', $pdId)->where('is_delete', 0)->first();
            if (!$row) {
                $this->sendErrorResponse('Department not found', 1);
                return;
            }

            if (!$this->assertDepartmentAccess($pdId)) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            if ($action === 'start' || $action === 'in_progress') {
                if (!$this->projectDepartmentService->canEditDepartment((array) $row)) {
                    $this->sendErrorResponse('Complete previous departments first', 1);
                    return;
                }
                $status = $action === 'start' ? ProjectDepartmentService::STATUS_START : ProjectDepartmentService::STATUS_IN_PROGRESS;
                $update = ['department_status' => $status];
                if ($action === 'in_progress' && empty($row->actual_start_date)) {
                    $update['actual_start_date'] = date('Y-m-d');
                }
                $this->projectDepartmentService->updateDepartmentRow($pdId, $update);
                $this->projectDepartmentService->syncProjectRollupStatus((int) $row->project_id);
                $this->projectDepartmentService->ensureWizardExecutionStep((int) $row->project_id);
                $this->sendSuccessResponse('Status updated', 'Update');
                return;
            }

            if ($action === 'complete') {
                $result = $this->projectDepartmentService->markCompleted($pdId);
                if (!empty($result['error'])) {
                    $this->sendErrorResponse($result['msg'] ?? 'Unable to complete', 1);
                    return;
                }
                $this->sendSuccessResponse($result['msg'], 'Update');
                return;
            }

            $this->sendErrorResponse('Invalid action', 1);
        } catch (\Exception $e) {
            Log::error('Department status error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function delay_panel(Request $request, $projectDepartmentId)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            return $this->panelDenied($request);
        }

        $ctx = $this->projectDepartmentService->resolveDepartment($projectDepartmentId);
        if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
            return $this->panelError($request, 'You do not have access to this department task');
        }

        $delays = DB::table('tbl_delay_registers')
            ->where('project_department_id', $ctx['id'])
            ->where('is_delete', 0)
            ->orderByDesc('id')
            ->get();

        $mitigations = [];
        if ($delays->isNotEmpty()) {
            $mitigations = DB::table('tbl_delay_mitigations')
                ->whereIn('delay_register_id', $delays->pluck('id'))
                ->where('is_delete', 0)
                ->orderByDesc('id')
                ->get()
                ->groupBy('delay_register_id');
        }

        $pageTitle = 'Delay Register — ' . $ctx['department_name'];
        $data = [
            'ctx' => $ctx,
            'delays' => $delays,
            'mitigations' => $mitigations,
            'root_causes' => $this->getRootCauses(),
            'register_statuses' => $this->getRegisterStatuses(),
        ];

        return $this->panelView($request, 'project_wizard.panels.delay-panel', compact('pageTitle', 'data'));
    }

    public function financial_panel(Request $request, $projectDepartmentId)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            return $this->panelDenied($request);
        }

        $ctx = $this->projectDepartmentService->resolveDepartment($projectDepartmentId);
        if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
            return $this->panelError($request, 'You do not have access to this department task');
        }

        $impact = DB::table('tbl_delay_financial_impacts')
            ->where('project_department_id', $ctx['id'])
            ->where('is_delete', 0)
            ->first();

        $pageTitle = 'Financial Impact — ' . $ctx['department_name'];
        $data = ['ctx' => $ctx, 'impact' => $impact];

        return $this->panelView($request, 'project_wizard.panels.financial-panel', compact('pageTitle', 'data'));
    }

    public function attachment_panel(Request $request, $projectDepartmentId)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            return $this->panelDenied($request);
        }

        $ctx = $this->projectDepartmentService->resolveDepartment($projectDepartmentId);
        if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
            return $this->panelError($request, 'You do not have access to this department task');
        }

        $attachments = DB::table('tbl_delay_attachments')
            ->where('project_department_id', $ctx['id'])
            ->where('is_delete', 0)
            ->orderByDesc('id')
            ->get();

        $pageTitle = 'Attachments — ' . $ctx['department_name'];
        $data = [
            'ctx' => $ctx,
            'attachments' => $attachments,
            'attachment_types' => $this->getAttachmentTypes(),
        ];

        return $this->panelView($request, 'project_wizard.panels.attachment-panel', compact('pageTitle', 'data'));
    }

    public function wizard_save_delay(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $pdId = (int) ($postData['project_department_id'] ?? 0);
            $ctx = $this->projectDepartmentService->resolveDepartment($pdId);
            if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            $title = trim($postData['delay_title'] ?? '');
            if ($title === '') {
                $this->sendValidationErrorResponse('<li>Please enter delay title</li>');
                return;
            }

            $registerId = $postData['delay_register_id'] ?? null;
            $operation = ($registerId && $registerId !== '') ? 'Update' : 'Add';

            $payload = [
                'project_id' => $ctx['project_id'],
                'project_department_id' => $ctx['id'],
                'delay_category_id' => $ctx['department_id'],
                'delay_title' => $title,
                'primary_delay_drivers' => trim($postData['primary_delay_drivers'] ?? ''),
                'specific_event_description' => trim($postData['specific_event_description'] ?? ''),
                'impacted_task' => trim($postData['impacted_task'] ?? ''),
                'responsibility_name' => trim($postData['responsibility_name'] ?? ''),
                'root_cause_id' => !empty($postData['root_cause_id']) ? (int) $postData['root_cause_id'] : null,
                'root_cause_label' => trim($postData['root_cause_label'] ?? ''),
                'delay_start_date' => $this->nullableDate($postData['delay_start_date'] ?? ''),
                'delay_end_date' => $this->nullableDate($postData['delay_end_date'] ?? ''),
                'target_revised_completion_date' => $this->nullableDate($postData['target_revised_completion_date'] ?? ''),
                'register_status' => $postData['register_status'] ?? 'open',
                'licensing_openings_affected' => !empty($postData['licensing_openings_affected']) ? 1 : 0,
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ];

            $payload = $this->delayRegisterService->applyAutoCalculations($payload);

            if ($operation === 'Add') {
                $payload['created_by'] = Auth::id();
                $payload['created_on'] = current_datetime();
                $payload['is_delete'] = 0;
                $newId = Common_model::addDataIntoTable('tbl_delay_registers', $payload);
                if ($newId) {
                    $this->projectDepartmentService->setDepartmentDelay($pdId);
                    $this->auditTrail->log('delay_register', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Delay logged', $operation);
                } else {
                    $this->sendErrorResponse('Save failed', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_delay_registers', '*', ['id' => $registerId], '', '', 'ASC', '', 0, true, '');
                Common_model::updateDataFromTable('tbl_delay_registers', $payload, 'id', $registerId);
                $this->projectDepartmentService->setDepartmentDelay($pdId);
                $this->auditTrail->log('delay_register', (int) $registerId, 'update', $old[0] ?? null, $payload);
                $this->sendSuccessResponse('Delay updated', $operation);
            }
        } catch (\Exception $e) {
            Log::error('Wizard save delay: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function wizard_save_mitigation(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $delayId = (int) ($postData['delay_register_id'] ?? 0);
            $action = trim($postData['mitigation_action'] ?? '');
            if ($delayId <= 0 || $action === '') {
                $this->sendValidationErrorResponse('<li>Delay and mitigation action are required</li>');
                return;
            }

            $delayRow = DB::table('tbl_delay_registers')->where('id', $delayId)->where('is_delete', 0)->first();
            if (!$delayRow || !$this->assertDepartmentAccess((int) ($delayRow->project_department_id ?? 0))) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            $mitigationId = $postData['mitigation_id'] ?? null;
            $operation = ($mitigationId && $mitigationId !== '') ? 'Update' : 'Add';
            $payload = [
                'delay_register_id' => $delayId,
                'mitigation_action' => $action,
                'owner_name' => trim($postData['action_owner'] ?? $postData['owner_name'] ?? ''),
                'target_resolution_date' => $this->nullableDate($postData['target_completion_date'] ?? $postData['target_resolution_date'] ?? ''),
                'current_status' => $postData['mitigation_status'] ?? $postData['current_status'] ?? 'open',
                'resolution_remarks' => trim($postData['remarks'] ?? $postData['resolution_remarks'] ?? ''),
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ];

            if ($operation === 'Add') {
                $payload['created_by'] = Auth::id();
                $payload['created_on'] = current_datetime();
                $payload['is_delete'] = 0;
                $newId = Common_model::addDataIntoTable('tbl_delay_mitigations', $payload);
                $this->sendSuccessResponse('Mitigation added', $operation);
            } else {
                Common_model::updateDataFromTable('tbl_delay_mitigations', $payload, 'id', $mitigationId);
                $this->sendSuccessResponse('Mitigation updated', $operation);
            }
        } catch (\Exception $e) {
            Log::error('Wizard save mitigation: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function wizard_save_financial(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $pdId = (int) ($postData['project_department_id'] ?? 0);
            $ctx = $this->projectDepartmentService->resolveDepartment($pdId);
            if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            $impactId = $postData['financial_impact_id'] ?? null;
            $operation = ($impactId && $impactId !== '') ? 'Update' : 'Add';

            $payload = $this->financialImpactService->applyCalculations([
                'labor_overrun' => $postData['labor_overrun'] ?? 0,
                'material_cost_overrun' => $postData['material_cost_overrun'] ?? 0,
                'contractor_claims' => $postData['contractor_claims'] ?? 0,
                'equipment_storage_charges' => $postData['equipment_storage_charges'] ?? 0,
                'delayed_admissions' => $postData['delayed_admissions'] ?? 0,
                'delayed_surgeries' => $postData['delayed_surgeries'] ?? 0,
                'delayed_revenue' => $postData['delayed_revenue'] ?? 0,
                'lost_operational_days' => $postData['lost_operational_days'] ?? 0,
            ]);

            $payload['project_id'] = $ctx['project_id'];
            $payload['project_department_id'] = $ctx['id'];
            $payload['delay_register_id'] = null;
            $payload['updated_by'] = Auth::id();
            $payload['updated_on'] = current_datetime();

            if ($operation === 'Add') {
                $payload['created_by'] = Auth::id();
                $payload['created_on'] = current_datetime();
                $payload['is_delete'] = 0;
                Common_model::addDataIntoTable('tbl_delay_financial_impacts', $payload);
            } else {
                Common_model::updateDataFromTable('tbl_delay_financial_impacts', $payload, 'id', $impactId);
            }

            $this->financialImpactService->syncProjectDelayCost((int) $ctx['project_id']);
            $this->sendSuccessResponse('Financial impact saved', $operation);
        } catch (\Exception $e) {
            Log::error('Wizard save financial: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function wizard_save_attachment(Request $request)
    {
        if (!$this->canManageDepartmentWorkflow()) {
            $this->sendErrorResponse('Permission missing.', 1);
            return;
        }

        try {
            $pdId = (int) $request->input('project_department_id');
            $ctx = $this->projectDepartmentService->resolveDepartment($pdId);
            if (!$ctx || !$this->assertDepartmentAccess((int) $ctx['id'])) {
                $this->sendErrorResponse('You do not have access to this department task', 1);
                return;
            }

            $attachmentId = $request->input('attachment_id');
            $operation = ($attachmentId && $attachmentId !== '') ? 'Update' : 'Add';
            $type = $request->input('attachment_type');
            if (empty($type)) {
                $this->sendValidationErrorResponse('<li>Select attachment type</li>');
                return;
            }

            $file = $request->file('attachment_file');
            if ($operation === 'Add' && (!$file || !$file->isValid())) {
                $this->sendValidationErrorResponse('<li>Please upload a file</li>');
                return;
            }

            $payload = [
                'project_id' => $ctx['project_id'],
                'project_department_id' => $ctx['id'],
                'delay_register_id' => null,
                'attachment_type' => $type,
                'description' => trim($request->input('description', '')),
                'updated_by' => Auth::id(),
                'updated_on' => current_datetime(),
            ];

            if ($file && $file->isValid()) {
                $originalName = $file->getClientOriginalName();
                $mime = $file->getMimeType();
                $size = $file->getSize();
                $dir = public_path('uploads/pdts/attachments');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $storedName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $file->move($dir, $storedName);
                $payload['file_name'] = $originalName;
                $payload['file_path'] = 'uploads/pdts/attachments/' . $storedName;
                $payload['mime_type'] = $mime;
                $payload['file_size'] = $size;
                $payload['uploaded_by'] = Auth::id();
                $payload['uploaded_on'] = current_datetime();
            }

            if ($operation === 'Add') {
                $payload['created_by'] = Auth::id();
                $payload['created_on'] = current_datetime();
                $payload['is_delete'] = 0;
                Common_model::addDataIntoTable('tbl_delay_attachments', $payload);
            } else {
                Common_model::updateDataFromTable('tbl_delay_attachments', $payload, 'id', $attachmentId);
            }

            $this->sendSuccessResponse('Attachment saved', $operation);
        } catch (\Exception $e) {
            Log::error('Wizard save attachment: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    private function panelView(Request $request, string $view, array $data)
    {
        if ($request->input('postKey') == 'sidelayoutContent') {
            return view($view, $data);
        }
        return redirect()->back();
    }

    private function panelDenied(Request $request)
    {
        if ($request->input('postKey') == 'sidelayoutContent') {
            return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
        }
        return redirect()->back()->with('error', 'You dont have permission to access this page');
    }

    private function panelError(Request $request, string $message)
    {
        if ($request->input('postKey') == 'sidelayoutContent') {
            return response()->json(['error' => 1, 'msg' => $message]);
        }
        return redirect()->back()->with('error', $message);
    }

    private function getProjectTypes(): array
    {
        if (!Schema::hasTable('tbl_project_types')) {
            return [];
        }
        return DB::table('tbl_project_types')
            ->where('is_delete', 0)->where('status', 1)->orderBy('type_name')
            ->get()->map(fn ($r) => ['id' => $r->id, 'label' => $r->type_name])->all();
    }

    private function getZones(): array
    {
        if (!Schema::hasTable('tbl_zones')) {
            return [];
        }
        return DB::table('tbl_zones')
            ->where('is_delete', 0)->where('status', 1)->orderBy('zone_name')
            ->get()->map(fn ($r) => ['id' => $r->id, 'label' => $r->zone_name])->all();
    }

    private function getActiveDepartments(): array
    {
        $table = $this->projectDepartmentService->departmentsTable();
        $nameCol = $this->projectDepartmentService->departmentNameColumn();
        return DB::table($table)
            ->where('is_delete', 0)->where('status', 1)
            ->orderBy('default_sort_order')->orderBy($nameCol)
            ->get(['id', DB::raw("$nameCol as department_name"), 'description'])
            ->map(fn ($r) => (array) $r)->all();
    }

    private function getRootCauses(): array
    {
        if (!Schema::hasTable('tbl_root_causes')) {
            return [];
        }
        return DB::table('tbl_root_causes')->where('is_delete', 0)->where('status', 1)->orderBy('cause_name')->get()->all();
    }

    private function getRegisterStatuses(): array
    {
        return [
            ['value' => 'open', 'label' => 'Open'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'closed', 'label' => 'Closed'],
        ];
    }

    private function getAttachmentTypes(): array
    {
        return [
            ['value' => 'photo', 'label' => 'Photo'],
            ['value' => 'drawing', 'label' => 'Drawing'],
            ['value' => 'noc', 'label' => 'NOC'],
            ['value' => 'approval_letter', 'label' => 'Approval Letter'],
            ['value' => 'vendor_communication', 'label' => 'Vendor Communication'],
            ['value' => 'change_order', 'label' => 'Change Order'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    private function validateProjectData(array $postData): string
    {
        $err = '';
        if (trim($postData['project_code'] ?? '') === '') {
            $err .= '<li>Please enter project ID / code</li>';
        }
        if (trim($postData['project_name'] ?? '') === '') {
            $err .= '<li>Please enter project name</li>';
        }
        $projectId = $postData['project_id'] ?? null;
        $code = trim($postData['project_code'] ?? '');
        if ($code !== '') {
            $q = DB::table('tbl_projects')->where('project_code', $code)->where('is_delete', 0);
            if ($projectId) {
                $q->where('id', '!=', $projectId);
            }
            if ($q->exists()) {
                $err .= '<li>Project ID already exists</li>';
            }
        }
        return $err;
    }

    private function prepareProjectData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $typeId = !empty($postData['project_type_id']) ? (int) $postData['project_type_id'] : null;
        $typeLabel = $typeId ? DB::table('tbl_project_types')->where('id', $typeId)->value('type_name') : null;
        $zoneId = !empty($postData['zone_id']) ? (int) $postData['zone_id'] : null;
        $zoneName = ($zoneId && Schema::hasTable('tbl_zones')) ? DB::table('tbl_zones')->where('id', $zoneId)->value('zone_name') : null;
        $locationId = !empty($postData['location_id']) ? (int) $postData['location_id'] : null;
        $locationName = trim($postData['location'] ?? '');
        if ($locationId && Schema::hasTable('tbl_locations')) {
            $locRow = DB::table('tbl_locations')->where('id', $locationId)->where('is_delete', 0)->first();
            if ($locRow) {
                $locationName = $locRow->location_name;
                if (!$zoneId) {
                    $zoneId = (int) $locRow->zone_id;
                    $zoneName = DB::table('tbl_zones')->where('id', $zoneId)->value('zone_name');
                }
            }
        }
        $spoc = trim($postData['project_spoc_name'] ?? '');
        $spocUserId = !empty($postData['project_spoc_user_id']) ? (int) $postData['project_spoc_user_id'] : null;
        if ($spocUserId) {
            $spocUser = DB::table('tbl_user')->where('id', $spocUserId)->where('status', ACTIVE)->first();
            if ($spocUser) {
                $spoc = trim(($spocUser->first_name ?? '') . ' ' . ($spocUser->last_name ?? ''));
            }
        }

        $data = [
            'project_code' => trim($postData['project_code']),
            'project_name' => trim($postData['project_name']),
            'project_scope' => trim($postData['project_scope'] ?? ''),
            'location' => $locationName,
            'hospital_name' => trim($postData['hospital_name'] ?? ''),
            'contractor_name' => trim($postData['contractor_name'] ?? ''),
            'zone_id' => $zoneId,
            'zone_department' => $zoneName ?: trim($postData['zone_department'] ?? ''),
            'area_facility' => trim($postData['area_facility'] ?? ''),
            'project_type_id' => $typeId,
            'project_type_label' => $typeLabel ?: trim($postData['project_type_label'] ?? ''),
            'project_spoc_name' => $spoc,
            'responsibility_name' => $spoc,
            'planned_start_date' => $this->nullableDate($postData['planned_start_date'] ?? ''),
            'planned_completion_date' => $this->nullableDate($postData['planned_completion_date'] ?? ''),
            'target_revised_completion_date' => $this->nullableDate($postData['target_revised_completion_date'] ?? ''),
            'updated_by' => $userId,
            'updated_on' => current_datetime(),
        ];

        if (Schema::hasColumn('tbl_projects', 'responsible_user_id')) {
            $data['responsible_user_id'] = $spocUserId ?: null;
        }

        if (Schema::hasColumn('tbl_projects', 'location_id')) {
            $data['location_id'] = $locationId;
        }

        if ($operation === 'Add') {
            $data['project_status'] = 'active';
            $data['wizard_step'] = 1;
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

    public function get_spoc_users(Request $request)
    {
        if (!$this->canAccessModule()) {
            return response()->json(['error' => 1, 'users' => []]);
        }

        $roleName = ($request->input('spoc_role') === 'project') ? 'Project SPOC' : 'Department SPOC';

        return response()->json([
            'error' => 0,
            'users' => $this->getSpocUserOptions($roleName),
        ]);
    }

    public function wizard_create_spoc_user(Request $request)
    {
        if (!modulePermissionExists($this->module) && !modulePermissionExists('users')) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $postData = $request->all();
            $departmentId = (int) ($postData['department_id'] ?? 0);
            $projectDepartmentId = (int) ($postData['project_department_id'] ?? 0);
            $isProjectSpoc = ($postData['spoc_role'] ?? 'department') === 'project';

            $errMessage = $this->validateSpocUserData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $spocRoleId = $this->getSpocRoleId($isProjectSpoc ? 'Project SPOC' : 'Department SPOC');
            if (!$spocRoleId) {
                $this->sendErrorResponse(($isProjectSpoc ? 'Project' : 'Department') . ' SPOC role is not configured. Run roles seeder.', 1);
                return;
            }

            $plainPassword = $postData['password'];
            $userId = Auth::id();
            $payload = [
                'first_name' => trim($postData['first_name']),
                'last_name' => trim($postData['last_name']),
                'email_id' => trim($postData['email_id']),
                'mobile_no' => trim($postData['mobile_no']),
                'password' => Hash::make($plainPassword),
                'user_type' => $spocRoleId,
                'status' => ACTIVE,
                'created_by' => $userId,
                'created_on' => current_datetime(),
                'updated_by' => $userId,
                'updated_on' => current_datetime(),
            ];

            $newUserId = Common_model::addDataIntoTable('tbl_user', $payload);
            if (!$newUserId) {
                $this->sendErrorResponse('Unable to create user. Try again later.', 1);
                return;
            }

            if (!$isProjectSpoc && $departmentId > 0) {
                $this->assignUserToDepartment((int) $newUserId, $departmentId);
            }

            if (!$isProjectSpoc && $projectDepartmentId > 0) {
                $spocName = trim($payload['first_name'] . ' ' . $payload['last_name']);
                $this->projectDepartmentService->updateDepartmentRow($projectDepartmentId, [
                    'spoc_user_id' => (int) $newUserId,
                    'spoc_name' => $spocName,
                ]);
            }

            $userOption = [
                'id' => (int) $newUserId,
                'label' => trim($payload['first_name'] . ' ' . $payload['last_name']) . ' — ' . $payload['email_id'],
                'name' => trim($payload['first_name'] . ' ' . $payload['last_name']),
            ];

            $roleName = $isProjectSpoc ? 'Project SPOC' : 'Department SPOC';
            $this->sendSuccessResponse('SPOC user created successfully', 'Add', '', [
                'user' => $userOption,
                'users' => $this->getSpocUserOptions($roleName),
                'spoc_role' => $isProjectSpoc ? 'project' : 'department',
            ]);
        } catch (\Exception $e) {
            Log::error('Wizard create SPOC user: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    private function getSpocUserOptions(?string $roleName = null, ?int $includeUserId = null): array
    {
        if (!Schema::hasTable('tbl_user')) {
            return [];
        }

        $query = DB::table('tbl_user as u')
            ->where('u.status', ACTIVE)
            ->orderBy('u.first_name')
            ->orderBy('u.last_name');

        if ($roleName) {
            $roleId = $this->getSpocRoleId($roleName);
            if ($roleId) {
                $query->where(function ($q) use ($roleId, $includeUserId) {
                    $q->where('u.user_type', $roleId);
                    if ($includeUserId) {
                        $q->orWhere('u.id', $includeUserId);
                    }
                });
            }
        }

        return $query
            ->get(['u.id', 'u.first_name', 'u.last_name', 'u.email_id'])
            ->map(fn ($user) => [
                'id' => (int) $user->id,
                'label' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) . ' — ' . ($user->email_id ?? ''),
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            ])
            ->all();
    }

    private function getSpocRoleId(string $roleName = 'Department SPOC'): ?int
    {
        $roleId = DB::table('tbl_roles')
            ->where('role_name', $roleName)
            ->where('status', ACTIVE)
            ->value('id');

        return $roleId ? (int) $roleId : null;
    }

    private function assignUserToDepartment(int $userId, int $departmentId): void
    {
        if ($departmentId <= 0) {
            return;
        }

        $existing = $this->userScope->getUserDepartmentIds($userId);
        if (!in_array($departmentId, $existing, true)) {
            $this->userScope->syncUserDepartments($userId, array_merge($existing, [$departmentId]));
        }
    }

    private function validateSpocUserData(array $postData): string
    {
        $errMessage = '';
        $fields = [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'email_id' => 'Email',
            'mobile_no' => 'Mobile number',
            'password' => 'Password',
        ];

        foreach ($fields as $field => $label) {
            if (trim($postData[$field] ?? '') === '') {
                $errMessage .= '<li>Please enter ' . $label . '</li>';
            }
        }

        if (!empty($postData['email_id'])) {
            if (Common_model::check_valid_email($postData['email_id']) == '0') {
                $errMessage .= '<li>Please enter a valid email</li>';
            }
            if (Common_model::check_exists('tbl_user', 'email_id', trim($postData['email_id']), '', []) > 0) {
                $errMessage .= '<li>User email already exists</li>';
            }
        }

        if (!empty($postData['mobile_no']) && Common_model::check_exists('tbl_user', 'mobile_no', trim($postData['mobile_no']), '', []) > 0) {
            $errMessage .= '<li>Mobile number already exists</li>';
        }

        if (!empty($postData['password']) && Common_model::check_valid_password($postData['password']) == '0') {
            $errMessage .= '<li>Password must be at least 8 characters with upper, lower, number and special character</li>';
        }

        return $errMessage;
    }

    private function parseWizardPost(Request $request): array
    {
        if ($request->has('data')) {
            $raw = $request->input('data');
            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (is_string($decoded)) {
                parse_str($decoded, $postData);
                return $postData ?? [];
            }
        }

        return $request->except(['_token']);
    }
}
