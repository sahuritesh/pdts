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

class LocationsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'locations';

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function location_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Location' : 'Create Location';
        $data = [
            'location' => '',
            'zones' => $this->getZones(),
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_locations',
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
                    $record['location_id'] = $record['id'];
                    $data['location'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Location edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('locations.create-location-form', compact('pageTitle', 'data'));
        }

        return view('locations.create-location', compact('pageTitle', 'data'));
    }

    public function insert_update_location(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateLocationData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $locationId = $postData['location_id'] ?? null;
            $operation = ($locationId && $locationId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareLocationData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_locations', $payload);
                if ($newId) {
                    $this->auditTrail->log('location', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Location added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_locations', '*', ['id' => $locationId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_locations', $payload, 'id', $locationId);
                if ($result) {
                    $this->auditTrail->log('location', (int) $locationId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Location updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Location Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function location_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Locations';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive'],
        ];
        $zoneOptions = array_map(
            fn ($z) => ['value' => $z['id'], 'label' => $z['label']],
            $this->getZones()
        );

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Code', 'Location Name', 'Zone', 'Description', 'Status', 'Created On'],
            'table' => 'tbl_locations',
            'dataurl' => 'get_location_list',
            'addurl' => 'locations/add',
            'addurllabel' => 'Add Location',
            'filters' => [
                $this->buildTextFilter('search', 'Search location', 'Search', 'col-md-3'),
                $this->buildSelectFilter('zone_filter', $zoneOptions, 'Zone', 'All zones', true, true, 'col-md-2'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_location_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';
            $zoneFilter = $filters['zone_filter'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($status) && $status !== 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }
            if (!empty($zoneFilter) && $zoneFilter !== 'All') {
                $wherecondition[] = ['column' => 'tb.zone_id', 'operator' => '', 'value' => (int) $zoneFilter, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.location_code', 'tb.location_name', 'tb.description', 'tz.zone_name'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'tz.zone_name'],
                ['', '', 'tb.location_code', 'tb.location_name', 'tz.zone_name', 'tb.description', 'tb.status', 'tb.created_on'],
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
                $editUrl = getProjectUrl('locations/edit/' . $id);
                $desc = $recordData->description ?? '';
                if (strlen($desc) > 60) {
                    $desc = substr($desc, 0, 60) . '…';
                }

                $recordListing[] = [
                    $this->wrapGridActions('<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Location\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>'),
                    $srNumber + 1,
                    e($recordData->location_code),
                    e($recordData->location_name),
                    e($recordData->zone_name ?? ''),
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
            Log::error('Get Location List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function get_locations_by_zone(Request $request)
    {
        if (!Schema::hasTable('tbl_locations')) {
            return response()->json(['error' => 0, 'locations' => []]);
        }

        $zoneId = (int) ($request->zone_id ?? $request->input('zone_id') ?? 0);

        $query = DB::table('tbl_locations')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('location_name');

        if ($zoneId > 0) {
            $query->where('zone_id', $zoneId);
        }

        $locations = $query->get(['id', 'location_code', 'location_name', 'zone_id']);

        return response()->json(['error' => 0, 'locations' => $locations]);
    }

    private function getZones(): array
    {
        if (!Schema::hasTable('tbl_zones')) {
            return [];
        }

        return DB::table('tbl_zones')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('zone_name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->zone_name])
            ->all();
    }

    private function validateLocationData(array $postData): string
    {
        $errMessage = '';
        $code = trim($postData['location_code'] ?? '');
        $name = trim($postData['location_name'] ?? '');
        $zoneId = (int) ($postData['zone_id'] ?? 0);

        if ($code === '') {
            $errMessage .= '<li>Please enter location code</li>';
        }
        if ($name === '') {
            $errMessage .= '<li>Please enter location name</li>';
        }
        if ($zoneId <= 0) {
            $errMessage .= '<li>Please select a zone</li>';
        }

        $locationId = $postData['location_id'] ?? null;
        if ($code !== '') {
            $query = DB::table('tbl_locations')->where('location_code', $code)->where('is_delete', 0);
            if ($locationId) {
                $query->where('id', '!=', $locationId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Location code already exists</li>';
            }
        }

        return $errMessage;
    }

    private function prepareLocationData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $data = [
            'location_code' => trim($postData['location_code']),
            'location_name' => trim($postData['location_name']),
            'zone_id' => (int) $postData['zone_id'],
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
