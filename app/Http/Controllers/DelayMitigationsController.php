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

class DelayMitigationsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = ['mitigations', 'mitigations_list'];

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function mitigation_add(Request $request, $delayRegisterId = '')
    {
        return $this->mitigation_form($request, '', $delayRegisterId);
    }

    public function mitigation_edit(Request $request, $id)
    {
        return $this->mitigation_form($request, $id, '');
    }

    public function mitigation_form(Request $request, $id = '', $delayRegisterId = '')
    {
        if (permissionexists($this->module[0]) != '1') {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Mitigation' : 'Add Mitigation';
        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $data = [
            'mitigation' => '',
            'delay_registers' => $this->getDelayRegisterOptions(),
            'statuses' => $this->getMitigationStatuses(),
            'preset_delay_register_id' => $presetDelayId,
            'panel_reload_url' => '',
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_delay_mitigations',
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
                    $record['mitigation_id'] = $record['id'];
                    $data['mitigation'] = $record;
                    $presetDelayId = (int) $record['delay_register_id'];
                    $data['preset_delay_register_id'] = $presetDelayId;
                }
            } catch (\Exception $e) {
                Log::error('Mitigation edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($presetDelayId) {
            $data['panel_reload_url'] = getProjectUrl('delay-mitigations/panel/' . Crypt::encrypt($presetDelayId));
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_mitigations.create-delay-mitigation-form', compact('pageTitle', 'data'));
        }

        return view('delay_mitigations.create-delay-mitigation', compact('pageTitle', 'data'));
    }

    public function mitigation_panel(Request $request, $delayRegisterId)
    {
        if (permissionexists($this->module[1]) != '1' && permissionexists($this->module[0]) != '1') {
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

        $pageTitle = 'Mitigations';
        $encryptedDelayId = Crypt::encrypt($delayId);
        $addUrl = getProjectUrl('delay-mitigations/add/' . $encryptedDelayId);
        $panelUrl = getProjectUrl('delay-mitigations/panel/' . $encryptedDelayId);
        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Action', 'Owner', 'Target Date', 'Status', 'Remarks', 'Actions'],
            'table' => 'tbl_delay_mitigations',
            'dataurl' => 'get_delay_mitigation_list',
            'addurl' => 'delay-mitigations/add/' . $encryptedDelayId,
            'addurllabel' => 'Add Mitigation',
            'filters' => [],
            'page_length' => 10,
        ]);

        $data = [
            'delay' => $delay,
            'delay_register_id' => $delayId,
            'encrypted_delay_id' => $encryptedDelayId,
            'add_url' => $addUrl,
            'panel_url' => $panelUrl,
        ];

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_mitigations.mitigation-panel', compact('pageTitle', 'data', 'grid_data'));
        }

        return redirect(getProjectUrl('delay-mitigations-list/' . $encryptedDelayId));
    }

    public function insert_update_mitigation(Request $request)
    {
        if (permissionexists($this->module[0]) != '1') {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateMitigationData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $mitigationId = $postData['mitigation_id'] ?? null;
            $operation = ($mitigationId && $mitigationId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareMitigationData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_delay_mitigations', $payload);
                if ($newId) {
                    $this->auditTrail->log('delay_mitigation', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Mitigation added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_delay_mitigations', '*', ['id' => $mitigationId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_delay_mitigations', $payload, 'id', $mitigationId);
                if ($result) {
                    $this->auditTrail->log('delay_mitigation', (int) $mitigationId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Mitigation updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Mitigation Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function mitigation_list(Request $request, $delayRegisterId = '')
    {
        if (permissionexists($this->module[1]) != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $pageTitle = 'Mitigation Tracking';
        $statusOptions = array_map(fn ($s) => ['value' => $s['value'], 'label' => $s['label']], $this->getMitigationStatuses());
        $delayOptions = $this->getDelayRegisterOptions();

        $delayFilter = $this->buildSelectFilter('delay_register_id', $delayOptions, 'Delay Entry', 'All delays', true, true, 'col-md-3');
        if ($presetDelayId) {
            $delayFilter['selected_value'] = $presetDelayId;
        }

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project', 'Delay Title', 'Mitigation Action', 'Owner', 'Target Date', 'Status', 'Actions'],
            'table' => 'tbl_delay_mitigations',
            'dataurl' => 'get_delay_mitigation_list',
            'addurl' => $presetDelayId
                ? 'delay-mitigations/add/' . Crypt::encrypt($presetDelayId)
                : 'delay-mitigations/add',
            'addurllabel' => 'Add Mitigation',
            'filters' => [
                $this->buildTextFilter('search', 'Search action, owner, remarks', 'Search', 'col-md-3'),
                $delayFilter,
                $this->buildSelectFilter('current_status', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_delay_mitigation_list(Request $request)
    {
        if (permissionexists($this->module[1]) != '1' && permissionexists($this->module[0]) != '1') {
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
            $currentStatus = $filters['current_status'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($delayRegisterId) && $delayRegisterId !== 'All') {
                $wherecondition[] = ['column' => 'tb.delay_register_id', 'operator' => '', 'value' => $delayRegisterId, 'condition' => 'and'];
            }
            if (!empty($currentStatus) && $currentStatus !== 'All') {
                $wherecondition[] = ['column' => 'tb.current_status', 'operator' => '', 'value' => $currentStatus, 'condition' => 'and'];
            }

            $joinsArray = [
                ['table_name' => 'tbl_delay_registers as dr', 'condition' => 'dr.id=tb.delay_register_id', 'join_type' => 'left'],
                ['table_name' => 'tbl_projects as tp', 'condition' => 'tp.id=dr.project_id', 'join_type' => 'left'],
            ];

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = [
                    'tb.mitigation_action', 'tb.owner_name', 'tb.resolution_remarks',
                    'dr.delay_title', 'tp.project_code', 'tp.project_name',
                ];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'dr.delay_title', 'tp.project_code', 'tp.project_name'],
                ['', 'tp.project_code', 'dr.delay_title', 'tb.mitigation_action', 'tb.owner_name', 'tb.target_resolution_date', 'tb.current_status'],
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
            $isPanel = !empty($panelDelayId);

            foreach ($getRecordListing['data'] as $recordData) {
                $id = Crypt::encrypt($recordData->id);
                $editUrl = getProjectUrl('delay-mitigations/edit/' . $id);
                $actionPreview = e($this->truncateText($recordData->mitigation_action ?? '', 80));
                $remarksPreview = e($this->truncateText($recordData->resolution_remarks ?? '', 60));

                if ($isPanel) {
                    $recordListing[] = [
                        $srNumber + 1,
                        $actionPreview,
                        e($recordData->owner_name ?? ''),
                        !empty($recordData->target_resolution_date) ? displayCustomDateTime($recordData->target_resolution_date) : '',
                        $this->formatStatusBadge($recordData->current_status),
                        $remarksPreview,
                        '<a href="javascript:void(0)" onclick="openMitigationEdit(\'' . $editUrl . '\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
                    ];
                } else {
                    $projectLabel = trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''));
                    $recordListing[] = [
                        $srNumber + 1,
                        e($projectLabel),
                        e($recordData->delay_title ?? ''),
                        $actionPreview,
                        e($recordData->owner_name ?? ''),
                        !empty($recordData->target_resolution_date) ? displayCustomDateTime($recordData->target_resolution_date) : '',
                        $this->formatStatusBadge($recordData->current_status),
                        '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Mitigation\', 85); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
                    ];
                }
                $srNumber++;
            }

            return response()->json([
                'draw' => (int) $draw,
                'recordsTotal' => $recordsFiltered,
                'recordsFiltered' => $recordsFiltered,
                'data' => $recordListing,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Mitigation List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateMitigationData(array $postData): string
    {
        $errMessage = '';
        if (empty($postData['delay_register_id'])) {
            $errMessage .= '<li>Please select a delay entry</li>';
        }
        if (trim($postData['mitigation_action'] ?? '') === '') {
            $errMessage .= '<li>Please enter mitigation action</li>';
        }
        return $errMessage;
    }

    private function prepareMitigationData(array $postData, string $operation): array
    {
        $userId = Auth::id();

        return [
            'delay_register_id' => (int) $postData['delay_register_id'],
            'mitigation_action' => trim($postData['mitigation_action']),
            'owner_user_id' => !empty($postData['owner_user_id']) ? (int) $postData['owner_user_id'] : null,
            'owner_name' => trim($postData['owner_name'] ?? ''),
            'target_resolution_date' => $this->nullableDate($postData['target_resolution_date'] ?? ''),
            'current_status' => $postData['current_status'] ?? 'open',
            'resolution_remarks' => trim($postData['resolution_remarks'] ?? ''),
            'updated_by' => $userId,
            'updated_on' => current_datetime(),
        ] + ($operation === 'Add' ? [
            'created_by' => $userId,
            'created_on' => current_datetime(),
            'is_delete' => 0,
        ] : []);
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

    private function getMitigationStatuses(): array
    {
        return [
            ['value' => 'open', 'label' => 'Open'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'closed', 'label' => 'Closed'],
        ];
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

    private function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function truncateText(?string $text, int $length): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\s+/', ' ', $text);
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '…';
        }
        return $text;
    }

    private function formatStatusBadge(?string $status): string
    {
        $map = [
            'open' => ['Open', 'badge-soft-warning'],
            'in_progress' => ['In Progress', 'badge-soft-info'],
            'escalated' => ['Escalated', 'badge-soft-danger'],
            'closed' => ['Closed', 'badge-soft-success'],
        ];
        $info = $map[$status] ?? [ucfirst(str_replace('_', ' ', (string) $status)), 'badge-soft-secondary'];

        return '<label class="badge rounded-pill ' . $info[1] . '">' . e($info[0]) . '</label>';
    }
}
