<?php

namespace App\Http\Controllers;

use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use App\Services\FinancialImpactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DelayFinancialImpactsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'financial_impacts';

    protected AuditTrailService $auditTrail;
    protected FinancialImpactService $financialImpactService;

    public function __construct(AuditTrailService $auditTrail, FinancialImpactService $financialImpactService)
    {
        $this->auditTrail = $auditTrail;
        $this->financialImpactService = $financialImpactService;
    }

    public function financial_impact_add(Request $request, $delayRegisterId = '')
    {
        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        if ($presetDelayId) {
            $existing = DB::table('tbl_delay_financial_impacts')
                ->where('delay_register_id', $presetDelayId)
                ->where('is_delete', 0)
                ->value('id');
            if ($existing) {
                return $this->financial_impact_form($request, Crypt::encrypt($existing), $delayRegisterId);
            }
        }

        return $this->financial_impact_form($request, '', $delayRegisterId);
    }

    public function financial_impact_edit(Request $request, $id)
    {
        return $this->financial_impact_form($request, $id, '');
    }

    public function financial_impact_form(Request $request, $id = '', $delayRegisterId = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Financial Impact' : 'Add Financial Impact';
        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $data = [
            'impact' => '',
            'delay_registers' => $this->getDelayRegisterOptions(),
            'preset_delay_register_id' => $presetDelayId,
            'panel_reload_url' => '',
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_delay_financial_impacts',
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
                    $record['financial_impact_id'] = $record['id'];
                    $data['impact'] = $record;
                    $presetDelayId = (int) $record['delay_register_id'];
                    $data['preset_delay_register_id'] = $presetDelayId;
                }
            } catch (\Exception $e) {
                Log::error('Financial impact edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($presetDelayId) {
            $data['panel_reload_url'] = getProjectUrl('delay-financial-impacts/panel/' . Crypt::encrypt($presetDelayId));
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_financial_impacts.create-delay-financial-impact-form', compact('pageTitle', 'data'));
        }

        return view('delay_financial_impacts.create-delay-financial-impact', compact('pageTitle', 'data'));
    }

    public function financial_impact_panel(Request $request, $delayRegisterId)
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $delayId = $this->resolveDelayRegisterId($delayRegisterId);
        if (!$delayId) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'Invalid delay register']);
            }
            return redirect()->back()->with('error', 'Invalid delay register');
        }

        $delay = DB::table('tbl_delay_registers as dr')
            ->leftJoin('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->where('dr.id', $delayId)
            ->where('dr.is_delete', 0)
            ->first(['dr.id', 'dr.delay_title', 'dr.severity', 'dr.delay_days', 'tp.project_code', 'tp.project_name']);

        if (!$delay) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'Delay entry not found']);
            }
            return redirect()->back()->with('error', 'Delay entry not found');
        }

        $encryptedDelayId = Crypt::encrypt($delayId);
        $existing = DB::table('tbl_delay_financial_impacts')
            ->where('delay_register_id', $delayId)
            ->where('is_delete', 0)
            ->first();

        $pageTitle = 'Financial Impact';
        $data = [
            'delay' => $delay,
            'delay_register_id' => $delayId,
            'encrypted_delay_id' => $encryptedDelayId,
            'impact' => $existing,
            'add_url' => getProjectUrl('delay-financial-impacts/add/' . $encryptedDelayId),
            'edit_url' => $existing ? getProjectUrl('delay-financial-impacts/edit/' . Crypt::encrypt($existing->id)) : '',
            'panel_url' => getProjectUrl('delay-financial-impacts/panel/' . $encryptedDelayId),
        ];

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_financial_impacts.financial-impact-panel', compact('pageTitle', 'data'));
        }

        return redirect(getProjectUrl('delay-financial-impacts-list/' . $encryptedDelayId));
    }

    public function insert_update_financial_impact(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateFinancialImpactData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $impactId = $postData['financial_impact_id'] ?? null;
            $operation = ($impactId && $impactId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareFinancialImpactData($postData, $operation);
            $payload = $this->financialImpactService->applyCalculations($payload);
            $projectId = (int) $payload['project_id'];

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_delay_financial_impacts', $payload);
                if ($newId) {
                    $this->financialImpactService->syncProjectDelayCost($projectId);
                    $this->auditTrail->log('delay_financial_impact', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Financial impact saved successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_delay_financial_impacts', '*', ['id' => $impactId], '', '', 'ASC', '', 0, true, '');
                $oldProjectId = (int) ($old[0]['project_id'] ?? 0);
                $result = Common_model::updateDataFromTable('tbl_delay_financial_impacts', $payload, 'id', $impactId);
                if ($result) {
                    $this->financialImpactService->syncProjectDelayCost($projectId);
                    if ($oldProjectId && $oldProjectId !== $projectId) {
                        $this->financialImpactService->syncProjectDelayCost($oldProjectId);
                    }
                    $this->auditTrail->log('delay_financial_impact', (int) $impactId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Financial impact updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Financial Impact Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function financial_impact_list(Request $request, $delayRegisterId = '')
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $pageTitle = 'Financial Impact';
        $delayOptions = $this->getDelayRegisterOptions();

        $delayFilter = $this->buildSelectFilter('delay_register_id', $delayOptions, 'Delay Entry', 'All delays', true, true, 'col-md-3');
        if ($presetDelayId) {
            $delayFilter['selected_value'] = $presetDelayId;
        }

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project', 'Delay Title', 'Direct Cost', 'Opportunity Cost', 'Total Cost', 'Updated', 'Actions'],
            'table' => 'tbl_delay_financial_impacts',
            'dataurl' => 'get_delay_financial_impact_list',
            'addurl' => $presetDelayId
                ? 'delay-financial-impacts/add/' . Crypt::encrypt($presetDelayId)
                : 'delay-financial-impacts/add',
            'addurllabel' => 'Add Financial Impact',
            'filters' => [
                $this->buildTextFilter('search', 'Search project or delay title', 'Search', 'col-md-3'),
                $delayFilter,
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_delay_financial_impact_list(Request $request)
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
            $panelDelayId = $request->input('panel_delay_register_id');

            $search = $filters['search'] ?? '';
            $delayRegisterId = $panelDelayId ?: ($filters['delay_register_id'] ?? '');

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($delayRegisterId) && $delayRegisterId !== 'All') {
                $wherecondition[] = ['column' => 'tb.delay_register_id', 'operator' => '', 'value' => $delayRegisterId, 'condition' => 'and'];
            }

            $joinsArray = [
                ['table_name' => 'tbl_delay_registers as dr', 'condition' => 'dr.id=tb.delay_register_id', 'join_type' => 'left'],
                ['table_name' => 'tbl_projects as tp', 'condition' => 'tp.id=dr.project_id', 'join_type' => 'left'],
            ];

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['dr.delay_title', 'tp.project_code', 'tp.project_name'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'dr.delay_title', 'tp.project_code', 'tp.project_name'],
                ['', 'tp.project_code', 'dr.delay_title', 'tb.direct_cost_total', 'tb.opportunity_cost_total', 'tb.total_project_delay_cost', 'tb.updated_on'],
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
                $editUrl = getProjectUrl('delay-financial-impacts/edit/' . $id);
                $projectLabel = trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''));

                $recordListing[] = [
                    $srNumber + 1,
                    e($projectLabel),
                    e($recordData->delay_title ?? ''),
                    $this->formatCurrency($recordData->direct_cost_total),
                    $this->formatCurrency($recordData->opportunity_cost_total),
                    $this->formatCurrency($recordData->total_project_delay_cost),
                    !empty($recordData->updated_on) ? displayCustomDateTime($recordData->updated_on) : '',
                    '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Financial Impact\', 90); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
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
            Log::error('Get Financial Impact List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateFinancialImpactData(array $postData): string
    {
        $errMessage = '';
        if (empty($postData['delay_register_id'])) {
            $errMessage .= '<li>Please select a delay entry</li>';
        }

        $impactId = $postData['financial_impact_id'] ?? null;
        $delayRegisterId = (int) ($postData['delay_register_id'] ?? 0);
        if ($delayRegisterId && !$impactId) {
            $exists = DB::table('tbl_delay_financial_impacts')
                ->where('delay_register_id', $delayRegisterId)
                ->where('is_delete', 0)
                ->exists();
            if ($exists) {
                $errMessage .= '<li>Financial impact already exists for this delay — please edit the existing record</li>';
            }
        }

        return $errMessage;
    }

    private function prepareFinancialImpactData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $delayRegisterId = (int) $postData['delay_register_id'];
        $projectId = (int) DB::table('tbl_delay_registers')
            ->where('id', $delayRegisterId)
            ->where('is_delete', 0)
            ->value('project_id');

        $data = [
            'delay_register_id' => $delayRegisterId,
            'project_id' => $projectId ?: null,
            'labor_overrun' => $postData['labor_overrun'] ?? 0,
            'material_cost_overrun' => $postData['material_cost_overrun'] ?? 0,
            'contractor_claims' => $postData['contractor_claims'] ?? 0,
            'equipment_storage_charges' => $postData['equipment_storage_charges'] ?? 0,
            'delayed_admissions' => $postData['delayed_admissions'] ?? 0,
            'delayed_surgeries' => $postData['delayed_surgeries'] ?? 0,
            'delayed_revenue' => $postData['delayed_revenue'] ?? 0,
            'lost_operational_days' => $postData['lost_operational_days'] ?? 0,
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

    private function getDelayRegisterOptions(): array
    {
        return DB::table('tbl_delay_registers as dr')
            ->leftJoin('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->where('dr.is_delete', 0)
            ->orderByDesc('dr.id')
            ->get(['dr.id', 'dr.delay_title', 'tp.project_code', 'tp.project_name'])
            ->map(function ($row) {
                $project = trim(($row->project_code ?? '') . ' — ' . ($row->project_name ?? ''));
                $title = $row->delay_title ?? 'Untitled delay';

                return [
                    'value' => $row->id,
                    'label' => ($project !== '—' ? $project . ': ' : '') . $title,
                ];
            })
            ->all();
    }

    private function resolveDelayRegisterId($encryptedOrPlain): ?int
    {
        if ($encryptedOrPlain === '' || $encryptedOrPlain === null) {
            return null;
        }
        if (is_numeric($encryptedOrPlain)) {
            return (int) $encryptedOrPlain;
        }
        try {
            return (int) Crypt::decrypt($encryptedOrPlain);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatCurrency($amount): string
    {
        return number_format((float) $amount, 2);
    }
}
