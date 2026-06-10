<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Http\Traits\EmailTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Crypt;

class UserManagement extends Controller
{
    use GridConfigTrait, WebResponseTrait, EmailTrait;

    public $module = 'users';

    /**
     * Display user create/edit form
     */
    public function user_management(Request $request, $param = '')
    {
        $res = modulePermissionExists($this->module) ? '1' : '0';
        if ($res != '1') {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $param ? 'Edit User' : 'Create User';

        $data = [
            'user' => '',
            'roles' => $this->getActiveRoles(),
            'status' => [
                ['id' => ACTIVE, 'status_name' => 'Active'],
                ['id' => INACTIVE, 'status_name' => 'Inactive']
            ],
            'backURL' => 'user-management-list',
            'exclude_roles' => []
        ];

        if ($param) {
            try {
                $decryptedId = Crypt::decrypt($param);
                $userData = Common_model::getDataFromTable(
                    'tbl_user',
                    '*',
                    ['id' => $decryptedId],
                    '',
                    'first_name',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($userData) && is_array($userData) && isset($userData[0])) {
                    $userRecord = $userData[0];
                    // Map 'id' to 'user_id' for form compatibility
                    if (isset($userRecord['id'])) {
                        $userRecord['user_id'] = $userRecord['id'];
                    }
                    $data['user'] = $userRecord;
                    $data['exclude_roles'] = [];
                } else {
                    Log::warning('User not found for edit. Decrypted ID: ' . $decryptedId);
                    $data['user'] = '';
                }
            } catch (\Exception $e) {
                Log::error('Error decrypting user ID for edit: ' . $e->getMessage());
                $data['user'] = '';
            }
        }

        // If request is from sidelayout, return only form content (no template)
        // Otherwise, return form wrapped in template for direct navigation
        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('users.create-users-form', compact('pageTitle', 'data'));
        }
        
        // For direct navigation, wrap form in template
        return view('users.create-users', compact('pageTitle', 'data'));
    }

    /**
     * Insert or update user
     */
    public function insert_update_user(Request $request)
    {
        $res = modulePermissionExists($this->module) ? '1' : '0';
        if ($res != '1') {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $postData = $request->post();
            $user_id = $postData['user_id'] ?? $postData['id'] ?? null;
            $operation = ($user_id && $user_id != "") ? 'Update' : 'Add';

            $errMessage = $this->validateUserData($postData, $operation, $user_id);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $prepared = $this->prepareUserData($postData, $operation);
            $data = $prepared['data'];
            $plainPassword = $prepared['plain_password'] ?? null;

            if ($operation == 'Add') {
                $result = $this->createUser($data, $plainPassword);
                $succ_msg = 'User added successfully';
                if ($result) {
                    $serialNumber = $this->generateSerialNumber($result);
                    $qrcodepath   = Common_model::generateQRCode($serialNumber, $serialNumber);

                    DB::table('tbl_user')
                        ->where('id', $result)
                        ->update([
                            'serial_number' => $serialNumber,
                            'qr_code'  => $qrcodepath
                        ]);
                }
            } else {
                $result = $this->updateUser($user_id, $data);
                $succ_msg = 'User updated successfully';
            }

            if ($result) {
                $this->sendSuccessResponse($succ_msg, $operation);
            } else {
                $this->sendErrorResponse('Something went wrong, try again later', 1);
            }
        } catch (\Exception $e) {
            Log::error('User Management Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Display user list with gridview
     */
    public function user_management_list(Request $request)
    {
        $res = modulePermissionExists($this->module) ? '1' : '0';
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'List of Users';
        $statusOptions = $this->getStatusOptions('Default');
        $roleOptions = $this->getRoleOptions();

        $filters = [
            $this->buildTextFilter('search', 'Search by Name, Email, Mobile, Role', 'Search', 'col-md-3'),
            $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2')
        ];

        if (Auth::user()->user_type == ADMIN) {
            $filters[] = $this->buildSelectFilter('role_id', $roleOptions, 'Role', 'Select role', true, true, 'col-md-2');
        }

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Full Name', 'Email', 'Mobile', 'Role', 'Status', 'Created On', 'Updated On'],
            'table' => 'tbl_user',
            'dataurl' => 'get_user_management_list',
            'addurl' => 'user-management/add',
            'filters' => $filters
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    /**
     * Get user list data for DataTables
     */
    public function get_user_management_list(Request $request)
    {
        $res = modulePermissionExists($this->module) ? '1' : '0';
        if ($res != '1') {
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];

            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';
            $role_id = $filters['role_id'] ?? '';

            $indexColumn = 'tb.id';
            $selectColumns = ['tb.*', 'tr.role_name'];
            $dataTableSortOrdering = [
                '', '', 'tb.first_name', 'tb.email_id', 'tb.mobile_no',
                'tr.role_name', 'tb.status', 'tb.created_on', 'tb.updated_on'
            ];
            $table_name = "$table as tb";

            $joinsArray = [
                ['table_name' => 'tbl_roles as tr', 'condition' => 'tr.id=tb.user_type', 'join_type' => 'left']
            ];

            $wherecondition = [];
            if (!empty($status) && $status != 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            if (!empty($role_id) && $role_id != 'All' && Auth::user()->user_type == ADMIN) {
                $wherecondition[] = ['column' => 'tb.user_type', 'operator' => '', 'value' => $role_id, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = [
                    'tb.first_name', 'tb.last_name',
                    'tb.email_id', 'tb.mobile_no', 'tr.role_name'
                ];
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
                '',
                '',
                1
            );

            $recordsFiltered = $getRecordListing['recordsFiltered'];
            $recordListing = [];
            $srNumber = $start;

            if (!empty($getRecordListing['data'])) {
                foreach ($getRecordListing['data'] as $recordData) {
                    $id = Crypt::encrypt($recordData->id);
                    $row = [];

                    $action = '';
                    if (Auth::user()->user_type == ADMIN) {
                        $editUrl = getProjectUrl('user-management/edit/' . $id);
                        $action = '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit User\'); return false;" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit User"><i class="ri-edit-fill"></i></a>&nbsp;';
                        $action .= '<a href="#" class="openforgotpassword" data-id="' . $recordData->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Forgot Password"><i class="ri-lock-unlock-fill"></i></a>';
                    }
                    $row[] = $action;
                    $row[] = $srNumber + 1;
                    $row[] = trim($recordData->first_name . ' ' . $recordData->last_name);
                    $row[] = $recordData->email_id;
                    $row[] = $recordData->mobile_no;
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
            Log::error('Get User List Error: ' . $e->getMessage());
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
     * Display change password form
     */
    public function change_password(Request $request)
    {
        $pageTitle = 'Change Password';
        return view('users.change-password', compact('pageTitle'));
    }

    /**
     * Update user password
     */
    public function update_change_password(Request $request)
    {
        try {
            $requestData = $request->post();
            $postData = json_decode($requestData['data'], true);

            $errMessage = $this->validatePasswordChange($postData);

            if (!empty($errMessage)) {
                return response()->json([
                    'error' => 1,
                    'msg' => ['error' => $errMessage]
                ]);
            }

            $user_id = Auth::user()->id ?? Auth::user()->user_id ?? null;
            $updateData = [
                'password' => Hash::make($postData['password']),
                'updated_by' => $user_id,
                'updated_on' => current_datetime()
            ];

            $result = Common_model::updateDataFromTable('tbl_user', $updateData, 'id', $user_id);

            if ($result) {
                return response()->json([
                    'error' => 0,
                    'msg' => 'New Password updated successfully'
                ]);
            }

            return response()->json([
                'error' => 1,
                'msg' => 'Something went wrong, try again later'
            ]);
        } catch (\Exception $e) {
            Log::error('Change Password Error: ' . $e->getMessage());
            return response()->json([
                'error' => 2,
                'msg' => 'An error occurred while processing your request'
            ]);
        }
    }

    /**
     * Send forgot password email (OTP)
     */
    public function send_forgotemail(Request $request)
    {
        $res = modulePermissionExists($this->module) ? '1' : '0';
        if ($res != '1') {
            return response()->json([
                'error' => 1,
                'msg' => ['error' => 'Permission missing. Contact administrator.']
            ]);
        }

        try {
            $requestData = $request->post();
            $postData = json_decode($requestData['data']);
            $user_id = $postData->id;
            $password = $postData->password;
            $userDetails = Common_model::getDataFromTable(
                'tbl_user',
                ['id', 'first_name', 'last_name', 'email_id', 'status'],
                ['id' => $user_id],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (empty($userDetails) || !isset($userDetails[0])) {
                return response()->json([
                    'error' => 1,
                    'msg' => ['error' => 'User not found']
                ]);
            }
            $user = $userDetails[0];
            // Check if user is active
            if (empty($user['status']) || $user['status'] != ACTIVE) {
                return response()->json([
                    'error' => 1,
                    'msg' => ['error' => 'This user account is inactive. Please contact support.']
                ]);
            }

            // Generate OTP (6 digits)
            // $otpCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            // $otpExpiry = now()->addMinutes(OTP_EXPIRY_MINUTES)->format('Y-m-d H:i:s');

            // Send OTP via email
            try {
                $userDataForEmail = [
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'] ?? '',
                    'email_id' => $user['email_id'],
                    'password' => $password
                ];

                $emailSent = $this->sendForgotPasswordEmail($userDataForEmail);

                if ($emailSent) {
                    // Save OTP to database
                    $updateData = [
                        'password' => Hash::make($password),
                        'updated_by' => Auth::id(), // Use tbl_user.id (primary key)
                        'updated_on' => current_datetime()
                    ];

                    $result = Common_model::updateDataFromTable('tbl_user', $updateData, 'id', $user_id);

                    if ($result) {
                        return response()->json([
                            'error' => 0,
                            'msg' => ['success' => 'Admin Reset Password Email sent successfully to user email address']
                        ]);
                    } else {
                        return response()->json([
                            'error' => 1,
                            'msg' => ['error' => 'Failed to send email. Please try again.']
                        ]);
                    }
                } else {
                    return response()->json([
                        'error' => 1,
                        'msg' => ['error' => 'Failed to send  email. Please try again later.']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Send Reset Password Email Error: ' . $e->getMessage());
                return response()->json([
                    'error' => 1,
                    'msg' => ['error' => 'Failed to send Reset email. Please try again later.']
                ]);
            }

            return response()->json([
                'error' => 1,
                'msg' => ['error' => 'Something went wrong. Please try again after sometime']
            ]);
        } catch (\Exception $e) {
            Log::error('Send Admin Reset Email Error: ' . $e->getMessage());
            Log::error($e);
            return response()->json([
                'error' => 2,
                'msg' => 'An error occurred while processing your request'
            ]);
        }
    }

    /**
     * Validate user data
     */
    private function validateUserData($postData, $operation, $user_id = null)
    {
        $errMessage = '';
        $mandatoryFields = ['first_name', 'last_name', 'email_id', 'mobile_no', 'user_type'];

        if ($operation == 'Add') {
            $mandatoryFields[] = 'password';
        }

        foreach ($mandatoryFields as $field) {
            if (empty(trim($postData[$field] ?? ''))) {
                $fieldName = ucwords(strtolower(str_replace("_", " ", $field)));
                $errMessage .= "<li>Please Enter $fieldName</li>";
            }
        }

        if (!empty($postData['email_id'])) {
            $emailValid = Common_model::check_valid_email($postData['email_id']);
            if ($emailValid == '0') {
                $errMessage .= '<li>Please Enter Valid Email</li>';
            }

            $emailExists = Common_model::check_exists(
                'tbl_user',
                'email_id',
                $postData['email_id'],
                $user_id ? 'id' : '',
                $user_id ? [$user_id] : []
            );
            if ($emailExists !== false && $emailExists > 0) {
                $errMessage .= '<li>User Email Already Exists</li>';
            }
        }

        if ($user_id) {
            $userDetails = Common_model::getDataFromTable(
                'tbl_user',
                '*',
                ['id' => $user_id],
                '',
                '',
                '',
                '',
                0,
                true,
                ''
            );

            if (!empty($userDetails[0])) {
                $mobileExists = Common_model::check_exists(
                    'tbl_user',
                    'mobile_no',
                    $postData['mobile_no'],
                    'id',
                    [$user_id]
                );
                if ($mobileExists !== false && $mobileExists > 0) {
                    $errMessage .= '<li>Mobile Number Already Exists</li>';
                }
            }
        } else {
            $mobileExists = Common_model::check_exists(
                'tbl_user',
                'mobile_no',
                $postData['mobile_no'],
                '',
                []
            );
            if ($mobileExists !== false && $mobileExists > 0) {
                $errMessage .= '<li>Mobile Number Already Exists</li>';
            }
        }

        if (isset($postData['password']) && !empty($postData['password'])) {
            $passwordValid = Common_model::check_valid_password($postData['password']);
            if ($passwordValid == '0') {
                $errMessage .= '<li>Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.</li>';
            }
        }

        return $errMessage;
    }

    /**
     * Prepare user data for insert/update
     */
    private function prepareUserData($postData, $operation)
    {
        unset($postData['_token']);

        $plainPassword = isset($postData['password']) ? $postData['password'] : '';
        $user_id = $postData['user_id'] ?? $postData['id'] ?? null;
        unset($postData['user_id']);
        unset($postData['id']);

        $currentUserId = Auth::user()->id ?? Auth::user()->user_id ?? null;

        if ($operation == 'Add') {
            $postData['password'] = Hash::make($postData['password']);
            $postData['created_by'] = $currentUserId;
            $postData['created_on'] = current_datetime();
        } else {
            if (isset($postData['password']) && !empty($postData['password'])) {
                $postData['password'] = Hash::make($postData['password']);
            } else {
                unset($postData['password']);
            }
            $postData['updated_by'] = $currentUserId;
            $postData['updated_on'] = current_datetime();
        }

        return [
            'data' => $postData,
            'plain_password' => $plainPassword
        ];
    }

    /**
     * Create new user
     */
    private function createUser($data, $plainPassword = null)
    {
        try {
            $userId = Common_model::addDataIntoTable('tbl_user', $data);

            if (!$userId) {
                Log::error('Failed to insert user');
                return false;
            }

            if ($userId && !empty($data['email_id']) && !empty($plainPassword)) {
                try {
                    $emailData = $data;
                    $emailData['recent_passwords'] = $plainPassword;
                    $this->sendWelcomeEmail($emailData);
                } catch (\Exception $e) {
                    Log::error('Welcome Email Error: ' . $e->getMessage());
                }
            }

            // Send new user onboarded notification to admin
            if ($userId && !empty($data['email_id'])) {
                try {
                    $userDataForNotification = [
                        'first_name' => $data['first_name'] ?? '',
                        'last_name' => $data['last_name'] ?? '',
                        'email_id' => $data['email_id'] ?? '',
                        'mobile_no' => $data['mobile_no'] ?? '',
                        'user_type' => $data['user_type'] ?? '',
                    ];
                    $this->sendNewUserOnboardedNotification($userDataForNotification);
                } catch (\Exception $e) {
                    // Log error but don't fail user creation
                    Log::error('New User Onboarded Notification Error: ' . $e->getMessage());
                }
            }

            return $userId;
        } catch (\Exception $e) {
            Log::error('Create User Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing user
     */
    private function updateUser($user_id, $data)
    {
        return Common_model::updateDataFromTable('tbl_user', $data, 'id', $user_id);
    }

    /**
     * Validate password change
     */
    private function validatePasswordChange($postData)
    {
        $errMessage = '';
        $mandatoryFields = ['current_password', 'password', 'confirm_new_password'];

        foreach ($mandatoryFields as $field) {
            if (empty(trim($postData[$field] ?? ''))) {
                $fieldName = ucwords(strtolower(str_replace("_", " ", $field)));
                $errMessage .= "<li>Please Enter $fieldName</li>";
            }
        }

        $auth = Auth::user();

        if (!empty($postData['current_password'])) {
            if (!Hash::check($postData['current_password'], $auth->password)) {
                $errMessage .= '<li>Current Password does not match</li>';
            }
        }

        if (!empty($postData['current_password']) && !empty($postData['password'])) {
            if (strcmp(strtolower($postData['current_password']), strtolower($postData['password'])) == 0) {
                $errMessage .= '<li>New Password cannot be same as your current password.</li>';
            }
        }

        if (!empty($postData['password']) && !empty($postData['confirm_new_password'])) {
            if (strcmp(strtolower($postData['password']), strtolower($postData['confirm_new_password'])) != 0) {
                $errMessage .= '<li>New Password and Confirm New Password not match</li>';
            }
        }

        return $errMessage;
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail($userData)
    {
        $userDataForEmail = [
            'first_name' => $userData['first_name'] ?? '',
            'last_name' => $userData['last_name'] ?? '',
            'email_id' => $userData['email_id'] ?? '',
            'password' => $userData['recent_passwords'] ?? '',
        ];

        return $this->sendWelcomeRegistrationEmail($userData['email_id'] ?? '', $userDataForEmail);
    }

    /**
     * Get active roles
     */
    private function getActiveRoles()
    {
        return Common_model::getDataFromTable(
            'tbl_roles',
            '*',
            [['status', '=', ACTIVE]],
            '',
            'role_name',
            'ASC',
            '',
            0,
            true,
            ''
        );
    }

    /**
     * Get role options for dropdown
     */
    private function getRoleOptions()
    {
        $rolesData = $this->getActiveRoles();
        $options = [];

        if (!empty($rolesData)) {
            foreach ($rolesData as $role) {
                $options[] = [
                    'value' => $role['id'],
                    'label' => $role['role_name']
                ];
            }
        }

        return $options;
    }

    /**
     * Get status options for dropdown
     */
    private function getStatusOptions($type = 'Default')
    {
        return [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive']
        ];
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

    private function generateSerialNumber($id)
    {
        // Use current year
        $year = date('Y');

        // Get last 2 digits of year
        $yearSuffix = substr($year, -2);

        // Pad ID to at least 2 digits
        $paddedId = str_pad($id, 2, '0', STR_PAD_LEFT);

        // Format: SPK/{paddedId}/{yearSuffix}
        return 'USER/' . $paddedId;
    }
}
