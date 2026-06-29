<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;

class RoleManagement extends Controller 
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'roles';
    
    /**
     * Display role create/edit form
     */
    public function role_management(Request $request, $id = '')
    {
        $res = permissionexists($this->module);
        if ($res != '1') {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Role' : 'Create Role';

        $permissionStructure = $this->getPermissionStructure();

        $data = [
            'roles' => '',
            'manage_permissions' => $permissionStructure
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $roleData = Common_model::getDataFromTable(
                    'tbl_roles',
                    '*',
                    ['id' => $decryptedId],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($roleData) && is_array($roleData) && isset($roleData[0])) {
                    $roleRecord = $roleData[0];
                    // Map 'id' to 'role_id' for form compatibility
                    if (isset($roleRecord['id'])) {
                        $roleRecord['role_id'] = $roleRecord['id'];
                    }
                    $data['roles'] = $roleRecord;
                } else {
                    Log::warning('Role not found for edit. Decrypted ID: ' . $decryptedId);
                    $data['roles'] = '';
                }
            } catch (\Exception $e) {
                Log::error('Error decrypting role ID for edit: ' . $e->getMessage());
                $data['roles'] = '';
            }
        }

        // If request is from sidelayout, return only form content (no template)
        // Otherwise, return form wrapped in template for direct navigation
        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('roles.create-roles-form', compact('pageTitle', 'data'));
        }
        
        // For direct navigation, wrap form in template
        return view('roles.create-roles', compact('pageTitle', 'data'));
    }

    /**
     * Insert or update role
     */
    public function insert_update_roles(Request $request)
    {
        try {
            $postData = array();
                $requestData = $request->post();
            parse_str(json_decode($requestData['data'], 1), $postData);

            $errMessage = $this->validateRoleData($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $role_id = $postData['role_id'] ?? null;
            $operation = ($role_id && $role_id != "") ? 'Update' : 'Add';

            $data = $this->prepareRoleData($postData, $operation);
                    
            if ($operation == 'Add') {
                $result = $this->createRole($data);
                $succ_msg = 'Role added successfully';
            } else {
                $result = $this->updateRole($role_id, $data);
                $succ_msg = 'Role updated successfully';
            }

            if ($result) {
                // Reload permissions for current user if they have the role that was just updated
                if ($operation == 'Update' && !empty($role_id)) {
                    $currentUser = Auth::user();
                    $currentUserRoleId = Session::get('effective_role_id', $currentUser->user_type ?? null);

                    if ((string) $currentUserRoleId === (string) $role_id) {
                        reloadCurrentUserPermissions();
                    }
                }
                
                $this->sendSuccessResponse($succ_msg, $operation);
            } else {
                $this->sendErrorResponse('Something went wrong, try again later', 1);
            }
        } catch (\Exception $e) {
            Log::error('Role Management Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
        }

    /**
     * Display role list with gridview
     */
    public function role_management_list(Request $request)
    {
            $res = permissionexists($this->module);
        if ($res != '1') {
                return redirect()->back()->with('error', 'You dont have permission to access this page');
            }

        $pageTitle = 'List of Roles';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive']
        ];

        $filters = [
            $this->buildTextFilter('search', 'Search by Role Name', 'Search', 'col-md-3'),
            $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2')
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Role Name', 'Status', 'Created On', 'Updated On'],
            'table' => 'tbl_roles',
            'dataurl' => 'get_role_management_list',
            'addurl' => 'role-management/add',
            'filters' => $filters
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    /**
     * Get role list data for DataTables
     */
    public function get_role_management_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];

            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $indexColumn = 'tb.id';
            $selectColumns = ['tb.*'];
            $dataTableSortOrdering = ['', '', 'tb.role_name', 'tb.status', 'tb.created_on', 'tb.updated_on'];
            $table_name = "$table as tb";

            $joinsArray = [];

            $wherecondition = [];     
            if (!empty($status) && $status != 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.role_name', 'tb.role_description'];
                $search_param = $search;
            }       

            $getRecordListing = Datatables_model::getDataTableResult(
                $selectColumns,
                $dataTableSortOrdering,
                $table_name,
                $joinsArray,
                $wherecondition,
                $indexColumn,
                $searchColumns,
                $search_param,
                $indexColumn,
                'DESC',
                ''
            );

            $recordsFiltered = $getRecordListing['recordsFiltered'];
            $recordListing = [];
            $srNumber = $start;

            if (!empty($getRecordListing['data'])) {
                foreach ($getRecordListing['data'] as $recordData) {
                    $id = Crypt::encrypt($recordData->id);
                    $row = [];

                    $editUrl = getProjectUrl('role-management/edit/' . $id);
                    $row[] = $this->wrapGridActions('<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Role\'); return false;" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Role"><i class="ri-edit-fill"></i></a>');
                    $row[] = $srNumber + 1;
                    $row[] = $recordData->role_name;
                    $row[] = $this->formatStatusBadge($recordData->status);
                    $row[] = displayCustomDateTime($recordData->created_on);
                    $row[] = displayCustomDateTime($recordData->updated_on);

                    $recordListing[] = $row;
                    $srNumber++;
                }
            }

            return response()->json([
                'draw' => (int)$draw,
                'recordsTotal' => $recordsFiltered,
                'recordsFiltered' => $recordsFiltered,
                'data' => $recordListing
            ]);
        } catch (\Exception $e) {
            Log::error('Get Role List Error: ' . $e->getMessage());
            Log::error($e);
            return response()->json([
                'draw' => (int)($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }
    }

    /**
     * Get permission structure for role form
     */
    private function getPermissionStructure()
    {
        return array_merge([
            'Dashboard' => [
                ['label' => 'View dashboard page', 'value' => 'dashboard_view'],
            ],
        ], self::getDashboardPermissionGroups(), [
            'User Management' => [
                ['label' => 'Roles', 'value' => 'roles'],
                ['label' => 'Users', 'value' => 'users'],
            ],
            'Project Tracking' => [
                ['label' => 'Departments', 'value' => 'departments'],
                ['label' => 'Hospitals', 'value' => 'hospitals'],
                ['label' => 'Locations', 'value' => 'locations'],
                ['label' => 'Tasks', 'value' => 'tasks'],
                ['label' => 'Projects (admin)', 'value' => 'projects'],
                ['label' => 'My Projects', 'value' => 'my_projects'],
                ['label' => 'My Department Tasks', 'value' => 'spoc_tasks'],
            ],
        ]);
    }

    /**
     * Sidebar module keys and legacy aliases (for roles saved before simplification).
     *
     * @return array<string, string[]>
     */
    public static function getModulePermissionAliases(): array
    {
        return [
            'users' => ['users_creation', 'users_list'],
            'departments' => ['delay_categories', 'delay_categories_list'],
            'hospitals' => [],
            'locations' => [],
            'tasks' => [],
            'projects' => ['projects_list', 'projects_create', 'delay_registers', 'mitigations', 'financial_impacts', 'delay_attachments'],
            'my_projects' => ['spoc_project_access', 'spoc_department_access'],
            'spoc_tasks' => [],
        ];
    }

    /** All sidebar module permission keys (for seeders). */
    public static function allModulePermissionKeys(): array
    {
        return [
            'dashboard_view',
            'roles',
            'users',
            'departments',
            'hospitals',
            'locations',
            'tasks',
            'projects',
            'my_projects',
            'spoc_tasks',
        ];
    }

    public static function modulePermissionExists(string $module): bool
    {
        if (permissionexists($module) == '1') {
            return true;
        }

        foreach (self::getModulePermissionAliases()[$module] ?? [] as $legacyKey) {
            if (permissionexists($legacyKey) == '1') {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate role data
     */
    private function validateRoleData($postData)
    {
        $errMessage = '';

        // Ensure $postData is an array
        if (!is_array($postData)) {
            return '<li>Invalid data format</li>';
        }

        // Check mandatory fields
        if (empty(trim($postData['role_name'] ?? ''))) {
            $errMessage .= '<li>Please Enter Role Name</li>';
            }	

        // Check role name uniqueness only if role_name exists
        if (!empty($postData['role_name'] ?? '')) {
            $role_id = $postData['role_id'] ?? null;
            $roleExists = Common_model::check_exists(
                'tbl_roles',
                'role_name',
                $postData['role_name'],
                $role_id ? 'id' : '',
                $role_id ? [$role_id] : []
            );

            if ($roleExists !== false && $roleExists > 0) {
                $errMessage .= '<li>Role Already Exists</li>';
            }
        }

        // Check permissions
        if (empty($postData['permission_types'] ?? [])) {
            $errMessage .= '<li>Select atleast one permission</li>';
        }

        return $errMessage;
    }

    /**
     * Prepare role data for insert/update
     */
    private function prepareRoleData($postData, $operation)
    {
        unset($postData['_token']);
        unset($postData['role_id']);

        // Convert permissions array to comma-separated string
        if (isset($postData['permission_types']) && is_array($postData['permission_types'])) {
            // Trim all permission values to remove any whitespace
            $postData['permission_types'] = implode(',', array_map('trim', $postData['permission_types']));
        }

        $currentUserId = Auth::user()->id ?? Auth::user()->user_id ?? null;

        if ($operation == 'Add') {
            $postData['status'] = ACTIVE;
            $postData['created_by'] = $currentUserId;
            $postData['created_on'] = current_datetime();
        } else {
            $postData['updated_by'] = $currentUserId;
            $postData['updated_on'] = current_datetime();
        }

        return $postData;
    }

    /**
     * Create new role
     */
    private function createRole($data)
    {
        return Common_model::addDataIntoTable('tbl_roles', $data);
    }

    /**
     * Update existing role
     */
    private function updateRole($role_id, $data)
    {
        return Common_model::updateDataFromTable('tbl_roles', $data, 'id', $role_id);
    }

    /**
     * Format status badge HTML
     */
    private function formatStatusBadge($status)
    {
        $statusName = ($status == ACTIVE) ? 'Active' : 'Inactive';
        $class = ($status == ACTIVE)
            ? 'badge rounded-pill badge-soft-success'
            : 'badge rounded-pill badge-soft-danger';

        return "<label class='$class'>$statusName</label>";
    }

    /**
     * Dashboard widget registry — each widget maps to one permission key on tbl_roles.permission_types.
     *
     * @return array<string, array{permission: string, module: int, label: string}>
     */
    public static function getDashboardWidgets(): array
    {
        return [
            'm1_kpis' => [
                'permission' => 'dashboard_m1_kpis',
                'module' => 1,
                'label' => 'Overview KPI cards',
            ],
            'm1_chart_severity' => [
                'permission' => 'dashboard_m1_chart_severity',
                'module' => 1,
                'label' => 'Delays by severity chart',
            ],
            'm1_chart_category' => [
                'permission' => 'dashboard_m1_chart_category',
                'module' => 1,
                'label' => 'Delayed departments chart',
            ],
            'm1_chart_project_status' => [
                'permission' => 'dashboard_m1_chart_project_status',
                'module' => 1,
                'label' => 'Project status chart',
            ],
            'm1_chart_mitigation' => [
                'permission' => 'dashboard_m1_chart_mitigation',
                'module' => 1,
                'label' => 'Department execution status chart',
            ],
            'm1_chart_financial' => [
                'permission' => 'dashboard_m1_chart_financial',
                'module' => 1,
                'label' => 'Financial impact chart',
            ],
            'm1_chart_trend' => [
                'permission' => 'dashboard_m1_chart_trend',
                'module' => 1,
                'label' => 'Delay trend chart',
            ],
            'm1_chart_hospital' => [
                'permission' => 'dashboard_m1_chart_hospital',
                'module' => 1,
                'label' => 'Delays by hospital chart',
            ],
            'm1_table_critical' => [
                'permission' => 'dashboard_m1_table_critical',
                'module' => 1,
                'label' => 'Delayed departments table',
            ],
            'm1_chart_zone' => [
                'permission' => 'dashboard_m1_chart_zone',
                'module' => 1,
                'label' => 'Zone-wise metrics chart',
            ],
        ];
    }

    /**
     * Dashboard permission groups for the role-management form.
     */
    public static function getDashboardPermissionGroups(): array
    {
        $groups = [
            'Dashboard — Project Tracking' => [],
        ];

        foreach (self::getDashboardWidgets() as $widget) {
            $groups['Dashboard — Project Tracking'][] = [
                'label' => $widget['label'],
                'value' => $widget['permission'],
            ];
        }

        return $groups;
    }

    /** All dashboard widget permission keys (for seeders). */
    public static function allDashboardPermissionKeys(): array
    {
        return array_values(array_map(
            fn ($widget) => $widget['permission'],
            self::getDashboardWidgets()
        ));
    }

    /**
     * Resolve visible dashboard widgets using session permissions (permissionexists).
     *
     * @return array<string, bool>
     */
    public static function resolveDashboardWidgets(): array
    {
        $visible = [];
        foreach (self::getDashboardWidgets() as $key => $widget) {
            $visible[$key] = permissionexists($widget['permission']) == '1';
        }

        return $visible;
    }

    public static function dashboardModuleHasWidgets(array $visibleWidgets, int $module): bool
    {
        foreach (self::getDashboardWidgets() as $key => $widget) {
            if ((int) $widget['module'] === $module && !empty($visibleWidgets[$key])) {
                return true;
            }
        }

        return false;
    }

    public static function dashboardHasAnyWidget(array $visibleWidgets): bool
    {
        return in_array(true, $visibleWidgets, true);
    }
}
