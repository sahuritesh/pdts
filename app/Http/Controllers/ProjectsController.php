<?php

namespace App\Http\Controllers;

use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Services\AuditTrailService;
use App\Services\UserScopeService;
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
    protected UserScopeService $userScope;

    public function __construct(AuditTrailService $auditTrail, UserScopeService $userScope)
    {
        $this->auditTrail = $auditTrail;
        $this->userScope = $userScope;
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
            'hospitals' => $this->getHospitals(),
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

            if ($operation === 'Update' && $this->userScope->isProjectCompleted((int) $projectId)) {
                $this->sendErrorResponse('This project is completed and cannot be edited.', 1);
                return;
            }

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
        if (!$this->userScope->hasFullProjectsPermission()) {
            if ($this->userScope->shouldUseMyProjectsListing()) {
                return redirect(getProjectUrl('my-projects-list'));
            }

            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Projects';
        $columns = ['Actions', '#', 'Project ID', 'Project Name', 'Hospital', 'Type', 'Zone', 'Area', 'SPOC', 'Status', 'Planned End'];
        $grid_data = $this->buildProjectGridConfig($columns, 'get_project_list', false);
        $grid_data['addurl'] = 'projects/wizard/new';
        $grid_data['addurl_redirect'] = true;
        $grid_data['addurllabel'] = 'Add Project';

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function my_project_list(Request $request)
    {
        if (!$this->userScope->shouldUseMyProjectsListing()) {
            if ($this->userScope->hasFullProjectsPermission()) {
                return redirect(getProjectUrl('projects-list'));
            }

            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $viewOnly = !$this->userScope->hasAssignedProjectsAsResponsible();
        $pageTitle = $viewOnly ? 'My Projects (View Only)' : 'My Projects';
        $columns = ['Actions', '#', 'Project ID', 'Project Name', 'Hospital', 'Type', 'Zone', 'Area', 'SPOC', 'Status', 'Planned End'];
        $grid_data = $this->buildProjectGridConfig($columns, 'get_my_project_list', false);
        $readonly = $viewOnly;

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data', 'readonly'));
    }

    public function get_project_list(Request $request)
    {
        if (!$this->userScope->hasFullProjectsPermission()) {
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        return $this->buildProjectListResponse($request, 'scoped', false);
    }

    public function get_my_project_list(Request $request)
    {
        if (!$this->userScope->shouldUseMyProjectsListing()) {
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        return $this->buildProjectListResponse($request, 'my_projects', false);
    }

    private function buildProjectGridConfig(array $columns, string $dataurl, bool $readonly): array
    {
        $statusOptions = array_map(function ($s) {
            return ['value' => $s['value'], 'label' => $s['label']];
        }, $this->getProjectStatuses());
        $hospitalOptions = array_map(
            fn ($h) => ['value' => $h['id'], 'label' => $h['label']],
            $this->getHospitals()
        );

        return $this->buildGridConfig([
            'columns' => $columns,
            'table' => 'tbl_projects',
            'dataurl' => $dataurl,
            'filters' => [
                $this->buildTextFilter('search', 'Search project, hospital, contractor', 'Search', 'col-md-3'),
                $this->buildSelectFilter('hospital', $hospitalOptions, 'Hospital', 'All hospitals', true, true, 'col-md-2'),
                $this->buildSelectFilter('project_status', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);
    }

    private function buildProjectListResponse(Request $request, string $scopeMode, bool $readonly)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $hospital = $filters['hospital'] ?? '';
            $projectStatus = $filters['project_status'] ?? '';
            $rollupStatus = $filters['rollup_status'] ?? '';
            $zoneIdFilter = $filters['zone_id'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($projectStatus) && $projectStatus !== 'All') {
                $wherecondition[] = ['column' => 'tb.project_status', 'operator' => '', 'value' => $projectStatus, 'condition' => 'and'];
            }
            if (!empty($zoneIdFilter) && $zoneIdFilter !== 'All') {
                $wherecondition[] = ['column' => 'tb.zone_id', 'operator' => '', 'value' => (int) $zoneIdFilter, 'condition' => 'and'];
            }
            if (!empty($hospital) && $hospital !== 'All') {
                if (DB::getSchemaBuilder()->hasColumn('tbl_projects', 'hospital_id')) {
                    $wherecondition[] = ['column' => 'tb.hospital_id', 'operator' => '', 'value' => (int) $hospital, 'condition' => 'and'];
                } else {
                    $hospitalName = DB::table('tbl_hospitals')->where('id', (int) $hospital)->value('hospital_name');
                    if ($hospitalName) {
                        $wherecondition[] = ['column' => 'tb.hospital_name', 'operator' => '', 'value' => $hospitalName, 'condition' => 'and'];
                    }
                }
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.project_code', 'tb.project_name', 'tb.hospital_name', 'tb.contractor_name', 'tb.project_spoc_name'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'tz.zone_name'],
                ['', '', 'tb.project_code', 'tb.project_name', 'tb.hospital_name', 'tb.project_type_label', 'tz.zone_name', 'tb.area_facility', 'tb.project_spoc_name', 'tb.project_status', 'tb.planned_completion_date'],
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

            $scopedProjectIds = $this->resolveScopedProjectIds($scopeMode);
            $rollupProjectIds = $this->resolveRollupProjectIds($rollupStatus, $scopedProjectIds);

            $recordsFiltered = 0;
            $recordListing = [];
            $srNumber = $start;

            foreach ($getRecordListing['data'] as $recordData) {
                if ($scopedProjectIds !== null && !in_array((int) $recordData->id, $scopedProjectIds, true)) {
                    continue;
                }
                if ($rollupProjectIds !== null && !in_array((int) $recordData->id, $rollupProjectIds, true)) {
                    continue;
                }

                $recordsFiltered++;
                $recordListing[] = $this->formatProjectListRow($recordData, $srNumber + 1, $readonly);
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

    /** @return int[]|null */
    private function resolveScopedProjectIds(string $scopeMode): ?array
    {
        if ($scopeMode === 'my_projects') {
            if (!Auth::check()) {
                return [];
            }

            $scopedQuery = DB::table('tbl_projects as tp')->where('tp.is_delete', 0);
            $this->userScope->applyMyProjectsScope($scopedQuery, 'tp');

            return $scopedQuery->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
        }

        if (!$this->userScope->isScopedUser()) {
            return null;
        }

        $scopedQuery = DB::table('tbl_projects as tp')->where('tp.is_delete', 0);
        $this->userScope->applyProjectScope($scopedQuery, 'tp');

        return $scopedQuery->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  int[]|null  $scopeIds
     * @return int[]|null
     */
    private function resolveRollupProjectIds(string $rollupStatus, ?array $scopeIds): ?array
    {
        $rollupStatus = trim($rollupStatus);
        if ($rollupStatus === '' || $rollupStatus === 'All') {
            return null;
        }

        $query = DB::table('tbl_projects as tp')->where('tp.is_delete', 0);
        if ($scopeIds !== null) {
            if ($scopeIds === []) {
                return [];
            }
            $query->whereIn('tp.id', $scopeIds);
        }

        if ($rollupStatus === 'on_hold') {
            return $query->where('tp.project_status', 'on_hold')->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
        }

        if (!DB::getSchemaBuilder()->hasTable('tbl_project_departments')) {
            if ($rollupStatus === 'delayed') {
                return $query->where('tp.project_status', 'delayed')->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
            }
            if ($rollupStatus === 'completed') {
                return $query->where('tp.project_status', 'completed')->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
            }
            if ($rollupStatus === 'active') {
                return $query->where('tp.project_status', 'active')->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
            }

            return [];
        }

        if ($rollupStatus === 'delayed') {
            return $query
                ->where('tp.project_status', '!=', 'on_hold')
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.project_id', 'tp.id')
                        ->where('pd.is_delete', 0)
                        ->where('pd.department_status', 'delay');
                })
                ->pluck('tp.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($rollupStatus === 'completed') {
            return $query
                ->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.project_id', 'tp.id')
                        ->where('pd.is_delete', 0);
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbl_project_departments as pd')
                        ->whereColumn('pd.project_id', 'tp.id')
                        ->where('pd.is_delete', 0)
                        ->where('pd.department_status', '!=', 'completed');
                })
                ->pluck('tp.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($rollupStatus === 'active') {
            $allIds = (clone $query)->pluck('tp.id')->map(fn ($id) => (int) $id)->all();
            $delayedIds = $this->resolveRollupProjectIds('delayed', $scopeIds) ?? [];
            $completedIds = $this->resolveRollupProjectIds('completed', $scopeIds) ?? [];
            $onHoldIds = $this->resolveRollupProjectIds('on_hold', $scopeIds) ?? [];
            $exclude = array_flip(array_merge($delayedIds, $completedIds, $onHoldIds));

            return array_values(array_filter($allIds, static fn ($id) => !isset($exclude[$id])));
        }

        return [];
    }

    private function formatProjectListRow(object $recordData, int $rowNumber, bool $readonly): array
    {
        $actionCell = '<span class="text-muted" title="View only">—</span>';
        if (!$readonly) {
            $id = Crypt::encrypt($recordData->id);
            $editUrl = getProjectUrl('projects/wizard/' . $id);
            if ($this->userScope->canEditProject((int) $recordData->id)) {
                $actionCell = '<a href="' . $editUrl . '" title="Open project wizard"><i class="ri-edit-fill"></i></a>';
            } elseif ($this->userScope->isProjectCompleted((int) $recordData->id)
                && $this->userScope->canAccessProject((int) $recordData->id)) {
                $actionCell = '<a href="' . $editUrl . '" title="View completed project (read-only)"><i class="ri-eye-line"></i></a>';
            }
        }

        return [
            $this->wrapGridActions($actionCell),
            $rowNumber,
            e($recordData->project_code),
            e($recordData->project_name),
            e($recordData->hospital_name ?? ''),
            e($recordData->project_type_label ?? ''),
            e($recordData->zone_name ?? $recordData->zone_department ?? ''),
            e($recordData->area_facility ?? ''),
            e($recordData->project_spoc_name ?? $recordData->responsibility_name ?? ''),
            $this->formatProjectStatusBadge($recordData->project_status),
            !empty($recordData->planned_completion_date) ? displayCustomDateTime($recordData->planned_completion_date) : '',
        ];
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

    private function getHospitals(): array
    {
        if (!DB::getSchemaBuilder()->hasTable('tbl_hospitals')) {
            return [];
        }

        return DB::table('tbl_hospitals')
            ->where('is_delete', 0)
            ->where('status', 1)
            ->orderBy('hospital_name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'label' => $r->hospital_name, 'code' => $r->hospital_code])
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

        $locationId = !empty($postData['location_id']) ? (int) $postData['location_id'] : null;
        $locationName = trim($postData['location'] ?? '');
        if ($locationId && DB::getSchemaBuilder()->hasTable('tbl_locations')) {
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

        $hospitalId = !empty($postData['hospital_id']) ? (int) $postData['hospital_id'] : null;
        $hospitalName = '';
        if ($hospitalId && DB::getSchemaBuilder()->hasTable('tbl_hospitals')) {
            $hospitalName = (string) (DB::table('tbl_hospitals')
                ->where('id', $hospitalId)
                ->where('is_delete', 0)
                ->value('hospital_name') ?? '');
        }

        $data = [
            'project_code' => trim($postData['project_code']),
            'project_name' => trim($postData['project_name']),
            'project_scope' => trim($postData['project_scope'] ?? ''),
            'location' => $locationName,
            'hospital_name' => $hospitalName,
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

        if (DB::getSchemaBuilder()->hasColumn('tbl_projects', 'location_id')) {
            $data['location_id'] = $locationId;
        }

        if (DB::getSchemaBuilder()->hasColumn('tbl_projects', 'hospital_id')) {
            $data['hospital_id'] = $hospitalId;
        }

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
