<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use Illuminate\Support\Facades\Log;

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
                    $currentUserRoleId = $currentUser->user_type ?? null;
                    
                    // If current user has the role that was just updated, reload their permissions
                    if ($currentUserRoleId == $role_id) {
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
            'columns' => ['#', 'Role Name', 'Status', 'Created On', 'Updated On', 'Actions'],
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
            $dataTableSortOrdering = ['', 'tb.role_name', 'tb.status', 'tb.created_on', 'tb.updated_on'];
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

                    $row[] = $srNumber + 1;
                    $row[] = $recordData->role_name;
                    $row[] = $this->formatStatusBadge($recordData->status);
                    $row[] = displayCustomDateTime($recordData->created_on);
                    $row[] = displayCustomDateTime($recordData->updated_on);
                    $editUrl = getProjectUrl('role-management/edit/' . $id);
                    $row[] = '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Role\'); return false;" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Role"><i class="ri-edit-fill"></i></a>';

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
        return [
            'Dashboard' => [
                ['label' => 'View dashboard', 'value' => 'dashboard_view'],
            ],
            'User Management' => [
                ['label' => 'User Creation', 'value' => 'users_creation'],
                ['label' => 'User List', 'value' => 'users_list'],
                ['label' => 'Roles', 'value' => 'roles']
            ],
            'Module 1 — Project Delay Tracking' => [
                ['label' => 'Delay category master', 'value' => 'delay_categories'],
                ['label' => 'Delay category listing', 'value' => 'delay_categories_list'],
                ['label' => 'Project master', 'value' => 'projects'],
                ['label' => 'Create / edit projects', 'value' => 'projects_create'],
                ['label' => 'Project listing', 'value' => 'projects_list'],
                ['label' => 'Delay register', 'value' => 'delay_registers'],
                ['label' => 'Create delay entry', 'value' => 'delay_registers_create'],
                ['label' => 'Delay listing', 'value' => 'delay_registers_list'],
                ['label' => 'Mitigation tracking', 'value' => 'mitigations'],
                ['label' => 'Mitigation listing', 'value' => 'mitigations_list'],
                ['label' => 'Financial impact', 'value' => 'financial_impacts'],
                ['label' => 'Financial impact listing', 'value' => 'financial_impacts_list'],
                ['label' => 'Delay attachments', 'value' => 'delay_attachments'],
            ],
            'Module 2 — Early Warning System' => [
                ['label' => 'EWS alerts', 'value' => 'ews_alerts'],
                ['label' => 'EWS configuration', 'value' => 'ews_config'],
            ],
            'Module 3 — Renovation Monitoring' => [
                ['label' => 'Renovation projects', 'value' => 'renovation_projects'],
                ['label' => 'Create renovation project', 'value' => 'renovation_projects_create'],
                ['label' => 'Renovation project listing', 'value' => 'renovation_projects_list'],
                ['label' => 'Renovation tasks', 'value' => 'renovation_tasks'],
                ['label' => 'Task listing', 'value' => 'renovation_tasks_list'],
                ['label' => 'Daily delay log', 'value' => 'renovation_daily_logs'],
                ['label' => 'Daily delay log listing', 'value' => 'renovation_daily_logs_list'],
                ['label' => 'Procurement tracking', 'value' => 'renovation_procurements'],
                ['label' => 'Approval tracking', 'value' => 'renovation_approvals'],
                ['label' => 'Change orders', 'value' => 'renovation_change_orders'],
                ['label' => 'Cost tracking', 'value' => 'renovation_costs'],
                ['label' => 'Risk scoring', 'value' => 'renovation_risks'],
            ],
            'Module 4 — Dashboards & Reports' => [
                ['label' => 'Executive dashboard', 'value' => 'executive_dashboard'],
                ['label' => 'Delay analytics', 'value' => 'delay_analytics'],
                ['label' => 'Renovation dashboard', 'value' => 'renovation_dashboard'],
                ['label' => 'Audit trail', 'value' => 'audit_trail'],
            ],
            'Settings' => [
                ['label' => 'System Settings', 'value' => 'settings'],
                ['label' => 'SMTP Settings', 'value' => 'smtp_settings'],
                ['label' => 'Payment Gateway Settings', 'value' => 'razorpay_settings'],
                ['label' => 'Email Templates', 'value' => 'email_templates'],
            ],
            'Notification Management' => [
                ['label' => 'Send Notifications', 'value' => 'send_push_notification'],
                ['label' => 'Notification listing', 'value' => 'push_notifications_listing'],
            ],
        ];
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
    }
