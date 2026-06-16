<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Common_model;
use App\Models\Datatables_model;
use App\Http\Traits\WebResponseTrait;

class ProfileController extends Controller
{
    use WebResponseTrait;

    /**
     * Display user profile page
     */
    public function myprofile()
    {
        try {
            $pageTitle = 'User Profile';
            $user = Auth::user();
            $uid = $user->id; // Use 'id' like other controllers
            
            // Query tbl_user table directly
            $userData = Common_model::getDataFromTable(
                'tbl_user',
                '*',
                ['id' => $uid],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (!empty($userData) && is_array($userData) && count($userData) > 0) {
                // Map 'id' to 'user_id' for form compatibility (form expects user_id)
                $userData[0]['user_id'] = $userData[0]['id'];
                $data['user'] = $userData[0];
            } else {
                $data['user'] = [];
            }           
            return view('users.update_profile', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('My Profile Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update user profile
     */
    public function update_profile(Request $request)
    {
        try {
            $postData = $request->post();
            $errMessage = $this->validateProfileData($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $preparedData = $this->prepareProfileData($postData);
            
            // Form sends 'user_id' but database uses 'id', so map it
            $user_id = isset($postData['user_id']) ? $postData['user_id'] : Auth::user()->id;
            unset($preparedData['user_id']);
            unset($preparedData['email_id']);

            // Use 'id' as the field name since that's the primary key in tbl_user
            $result = Common_model::updateDataFromTable('tbl_user', $preparedData, 'id', $user_id);

            if ($result) {
                $this->sendSuccessResponse('Profile updated successfully', 'Update');
            } else {
                $this->sendErrorResponse('Something went wrong, try again later', 1);
            }
        } catch (\Exception $e) {
            Log::error('Update Profile Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Display change password page
     */
    public function changepassword()
    {
        try {
            $pageTitle = 'Change Password';
            $user = Auth::user();
            $uid = $user->id;
            
            $data['user'] = Common_model::getDataFromTable(
                'tbl_user',
                '*',
                ['id' => $uid],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );
            
            // Map 'id' to 'user_id' for view compatibility (if view expects user_id)
            if (!empty($data['user']) && is_array($data['user']) && count($data['user']) > 0) {
                $data['user'][0]['user_id'] = $data['user'][0]['id'];
            }

            return view('users.change-password', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('Change Password Page Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update user password
     */
    public function update_password(Request $request)
    {
        try {
            $postData = $request->post();
            $errMessage = $this->validatePasswordData($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $user = Auth::user();
            $userId = $user->id; // Use 'id' like login does (line 79, 84 in LoginController)
            $oldPassword = $postData['current_password'];
            $newPassword = $postData['password'];
            $newhashPass = Hash::make($newPassword);

            // Verify current password using Auth::attempt (same method as login - more reliable)
            // This uses Laravel's authentication which knows how to check passwords correctly
            if (!Auth::attempt(['email_id' => $user->email_id, 'password' => $oldPassword])) {
                $this->sendErrorResponse('The current password is incorrect', 1);
                return;
            }

            // Update password
            $Data['password'] = $newhashPass;
            $Data['updated_by'] = $userId;
            $Data['updated_on'] = current_datetime();

            // Use 'id' as field name since that's the primary key (same as login uses)
            $result = Common_model::updateDataFromTable('tbl_user', $Data, 'id', $userId);

            if ($result) {
                $this->sendResponse(0, 'Password Updated Successfully', 'Update', url('/logout'));
            } else {
                $this->sendErrorResponse('Something went wrong, try again later', 1);
            }
        } catch (\Exception $e) {
            Log::error('Update Password Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Get notification count
     */
    public function getNotificationCnt()
    {
/*
        try {
            $user = auth()->user();
            if (!$user) {
                $res['error'] = 0;
                $res['noticationcnt'] = '0';
                echo json_encode($res);
                return;
            }

            $where = $this->getNotificationWhereCondition($user);
            $notifications = Common_model::getDataFromTable(
                'tbl_notifications',
                ['message'],
                $where,
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            $res['error'] = 0;
            $res['noticationcnt'] = (!empty($notifications) && is_array($notifications)) ? count($notifications) : '0';
            echo json_encode($res);
        } catch (\Exception $e) {
            Log::error('Get Notification Count Error: ' . $e->getMessage());
            Log::error($e);
            $res['error'] = 0;
            $res['noticationcnt'] = '0';
            echo json_encode($res);
        }

        */
    }

    /**
     * Get notifications list
     */
    public function getNotifications()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                $res['error'] = 0;
                $res['html'] = $this->getEmptyNotificationHtml();
                echo json_encode($res);
                return;
            }

            $where = $this->getNotificationWhereCondition($user);
            $notifications = Common_model::getDataFromTable(
                'tbl_notifications',
                ['id', 'message', 'type', 'notification_refer_id'],
                $where,
                '',
                'created_on',
                'DESC',
                '3',
                0,
                true,
                ''
            );

            $res['error'] = 0;
            if (!empty($notifications) && is_array($notifications)) {
                $response = '';
                foreach ($notifications as $data) {
                    $response .= '<a href="javascript:void(0)" class="text-reset notification-item" data-value="' . $data['id'] . '" data-type="' . $data['type'] . '" data-typeid="' . $data['notification_refer_id'] . '">
                        <div class="d-flex">
                            <div class="avatar-xs me-3">
                                <span class="avatar-title bg-primary rounded-circle font-size-16">
                                    <i class="ri-notification-3-fill"></i>
                                </span>
                            </div>
                            <div class="flex-1">
                                <h6 class="mb-1">' . $data['type'] . '</h6>
                                <div class="font-size-12 text-muted">
                                    <p class="mb-1">' . $data['message'] . '</p>                                
                                </div>
                            </div>
                        </div>
                    </a>';
                }
                $res['html'] = $response;
            } else {
                $res['html'] = $this->getEmptyNotificationHtml();
            }
            echo json_encode($res);
        } catch (\Exception $e) {
            Log::error('Get Notifications Error: ' . $e->getMessage());
            Log::error($e);
            $res['error'] = 0;
            $res['html'] = $this->getEmptyNotificationHtml();
            echo json_encode($res);
        }
    }

    /**
     * Display all notifications list page
     */
    public function getallNotifications()
    {
        try {
            $pageTitle = 'List of Notifications';
            $grid_data['columns'] = ['Action', '#', 'Type', 'Notification', 'Status', 'Created On'];
            $grid_data['no_sort_columns'] = ['Action'];
            $grid_data['export_columns'] = ':not(:first-child)';
            $grid_data['table'] = 'tbl_notifications';
            $grid_data['dataurl'] = 'get_notification_list';
            $grid_data['selected_value'] = NOTIFYUNREAD;
            
            $grid_data['status'] = Common_model::getDataFromTable(
                'tbl_status',
                '*',
                ['type' => 'Notification'],
                '',
                'id',
                'ASC',
                '',
                0,
                true,
                ''
            );

            return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
        } catch (\Exception $e) {
            Log::error('Get All Notifications Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update notification status
     */
    public function updateNotificationStatus(Request $request)
    {
        try {
            $user_id = Auth::id(); // Use tbl_user.id (primary key)
            $requestData = $request->post();
            $postData = $requestData['data'];
            $result = json_decode($postData);
            $notification_id = $result->id;

            $Data['status'] = NOTIFYREAD;
            $Data['updated_by'] = $user_id;
            $Data['updated_on'] = current_datetime();

            $update = Common_model::updateDataFromTable('tbl_notifications', $Data, 'id', $notification_id);

            if ($update) {
                $res['error'] = 0;
                $res['msg'] = 'updated';
            } else {
                $res['error'] = 1;
                $res['msg'] = 'Failed to update notification';
            }
            echo json_encode($res);
        } catch (\Exception $e) {
            Log::error('Update Notification Status Error: ' . $e->getMessage());
            Log::error($e);
            $res['error'] = 1;
            $res['msg'] = 'Something went wrong';
            echo json_encode($res);
        }
    }

    /**
     * Get notification list for DataTables
     */
    public function get_notification_list(Request $request)
    {
        try {
            $user_id = Auth::id(); // Use tbl_user.id (primary key)
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $indexColumn = 'tb.id';
            $selectColumns = ['tb.id', 'tb.type', 'tb.message', 'tb.status', 'tb.created_on', 'tt.status_name', 'tt.class', 'tb.notification_refer_id'];
            $dataTableSortOrdering = ['', '', 'tb.type', 'tb.message', 'tb.status', 'tb.created_on'];
            $table_name = "$table as tb";
            $joinsArray[] = ['table_name' => 'tbl_status as tt', 'condition' => 'tt.id=tb.status', 'join_type' => 'left'];

            $wherecondition = [];
            if (!empty($status) && $status != 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $wherecondition = array_merge($wherecondition, $this->getNotificationListWhereConditions($user_id));

            $nullConditions['tb.notification_refer_id'] = 'IS NOT NULL';
            $searchColumns = $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.type', 'tb.notification_type', 'tb.message'];
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
                1,
                '',
                $nullConditions
            );

            $totalRecords = $getRecordListing['recordsTotal'] ?? 0;
            $recordsFiltered = $getRecordListing['recordsFiltered'] ?? 0;
            $recordListing = [];

            if (!empty($getRecordListing['data'])) {
                $srNumber = $start;
                foreach ($getRecordListing['data'] as $recordData) {
                    $record = [];
                    $record[] = '<span class="grid-actions"><a href="view-notification/' . $recordData->type . '/' . $recordData->id . '/' . $recordData->notification_refer_id . '" ><i class="fa fa-eye"></i></a></span>';
                    $record[] = $srNumber + 1;
                    $record[] = $recordData->type;
                    $record[] = $recordData->message;
                    $record[] = "<label class='$recordData->class'>" . $recordData->status_name . "</label>";
                    $record[] = ($recordData->created_on) ? displayCustomDateTime($recordData->created_on) : '';
                    $recordListing[] = $record;
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
            Log::error('Get Notification List Error: ' . $e->getMessage());
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
     * Get single notification details
     */
    public function getNotification(Request $request)
    {
        try {
            $requestData = $request->post();
            $postData = json_decode($requestData['data'], true);
            $where = ['id' => $postData['notify_id']];

            $notifications = Common_model::getDataFromTable(
                'tbl_notifications',
                ['id', 'message', 'type'],
                $where,
                '',
                'created_on',
                'DESC',
                '',
                0,
                true,
                ''
            );

            $res['error'] = 0;
            $response = '';

            if (!empty($notifications) && is_array($notifications) && count($notifications) > 0) {
                $response .= '
                    <div class="d-flex">
                        <div class="avatar-xs me-3">
                            <span class="avatar-title bg-primary rounded-circle font-size-16">
                                <i class="ri-notification-3-fill"></i>
                            </span>
                        </div>
                        <div class="flex-1">
                            <h6 class="mb-1">' . $notifications[0]['type'] . '</h6>
                            <div class="font-size-12 text-muted">
                                <p class="mb-1">' . $notifications[0]['message'] . '</p>                                
                            </div>
                        </div>
                    </div>
                ';
            } else {
                $response .= '
                    <div class="d-flex">
                        <div class="avatar-xs me-3">
                            <span class="avatar-title bg-primary rounded-circle font-size-16">
                                <i class="ri-notification-3-fill"></i>
                            </span>
                        </div>
                        <div class="flex-1">
                            <div class="font-size-12 text-muted">
                                <p class="mb-1">Notification not found</p>                                
                            </div>
                        </div>
                    </div>
                ';
            }
            $res['html'] = $response;
            echo json_encode($res);
        } catch (\Exception $e) {
            Log::error('Get Notification Error: ' . $e->getMessage());
            Log::error($e);
            $res['error'] = 1;
            $res['html'] = '<p>Error loading notification</p>';
            echo json_encode($res);
        }
    }

    /**
     * Validate profile data
     */
    private function validateProfileData($postData)
    {
        $errMessage = '';
        $mandatoryFields = ['first_name', 'email_id', 'mobile_no'];

        foreach ($postData as $fieldname => $fieldValue) {
            if (!is_array($postData[$fieldname])) {
                $fieldValue = trim($postData[$fieldname]);
                if (empty($fieldValue) && in_array($fieldname, $mandatoryFields)) {
                    $fieldname = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                    $errMessage .= "<li>Please Enter $fieldname</li>";
                }
            }
        }

        if (!empty($postData['email_id'])) {
            // Form sends 'user_id' but database uses 'id', so map it
            $userId = isset($postData['user_id']) ? $postData['user_id'] : (isset($postData['id']) ? $postData['id'] : null);
            
            if ($userId) {
                $isCustomerExist = Common_model::check_exists(
                    'tbl_user',
                    'email_id',
                    $postData['email_id'],
                    'id',
                    [$userId]
                );
            } else {
                $isCustomerExist = Common_model::check_exists(
                    'tbl_user',
                    'email_id',
                    $postData['email_id'],
                    '',
                    []
                );
            }

            if (!empty($isCustomerExist) && $isCustomerExist > 0) {
                $errMessage .= "<li>Email Id Already Exists</li>";
            }
        }

        return $errMessage;
    }

    /**
     * Prepare profile data for update
     */
    private function prepareProfileData($postData)
    {
        unset($postData['_token']);
        $postData['updated_by'] = Auth::user()->id;
        $postData['updated_on'] = current_datetime();
        return $postData;
    }

    /**
     * Validate password data
     */
    private function validatePasswordData($postData)
    {
        $errMessage = '';
        $mandatoryFields = ['password', 'current_password', 'confirm_password'];

        foreach ($postData as $fieldname => $fieldValue) {
            if (!is_array($postData[$fieldname])) {
                $fieldValue = trim($postData[$fieldname]);
                if (empty($fieldValue) && in_array($fieldname, $mandatoryFields)) {
                    $fieldname = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                    $errMessage .= "<li>Please Enter $fieldname</li>";
                }
            }
        }

        if (isset($postData['password']) && $postData['password'] != '') {
            $passwordvalid = Common_model::check_valid_password($postData['password']);
            if ($passwordvalid == '0') {
                $errMessage .= '<li>Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.</li>';
            }
        }

        if (isset($postData['confirm_password']) && $postData['confirm_password'] != '') {
            $passwordvalid = Common_model::check_valid_password($postData['confirm_password']);
            if ($passwordvalid == '0') {
                $errMessage .= '<li>Confirm Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.</li>';
            }
        }

        // Validate password match
        if (isset($postData['password']) && isset($postData['confirm_password']) &&
            !empty($postData['password']) && !empty($postData['confirm_password'])) {
            if ($postData['password'] != $postData['confirm_password']) {
                $errMessage .= "<li>New Password And Confirm Password Didn't Match</li>";
            }
        }

        return $errMessage;
    }

    /**
     * Get notification where condition based on user type
     */
    private function getNotificationWhereCondition($user)
    {
        $where = ['to_id' => $user->user_id, 'status' => NOTIFYUNREAD];

        if ($user->user_type == SALES_MANAGER) {
            $where = ['notification_type' => 'role', 'status' => NOTIFYUNREAD];
        } elseif ($user->user_type == ADMIN || $user->user_type == CRM_AGENT) {
            $where = ['notification_type' => 'role', 'type' => ['Leave', 'Task', 'Larvicide', 'Routine'], 'status' => NOTIFYUNREAD];
        } elseif ($user->user_type == TECHNICIAN) {
            $where = ['notification_type' => 'role', 'type' => 'Leave', 'status' => NOTIFYUNREAD, 'to_id' => $user->user_id];
        } elseif ($user->user_type == STORE_KEEPER) {
            $where = ['to_id' => $user->user_type, 'status' => NOTIFYUNREAD];
        } elseif ($user->user_type == MOSQUITO_CONTROL) {
            $where = ['notification_type' => 'Leave', 'type' => 'Leave', 'status' => NOTIFYUNREAD];
        }

        return $where;
    }

    /**
     * Get notification list where conditions based on user type
     */
    private function getNotificationListWhereConditions($user_id)
    {
        $wherecondition = [];

        if (Auth::user()->user_type == SALES_TEAM || Auth::user()->user_type == CALL_CENTER) {
            $wherecondition[] = ['column' => 'tb.to_id', 'operator' => '', 'value' => $user_id, 'condition' => 'and'];
        } elseif (Auth::user()->user_type == SALES_MANAGER) {
            $wherecondition[] = ['column' => 'tb.notification_type', 'operator' => '', 'value' => 'role', 'condition' => 'and'];
        } elseif (Auth::user()->user_type == ADMIN || Auth::user()->user_type == CRM_AGENT) {
            $wherecondition[] = ['column' => 'tb.notification_type', 'operator' => '', 'value' => 'role', 'condition' => 'and'];
            $wherecondition[] = ['column' => 'tb.type', 'operator' => '', 'value' => ['Leave', 'Task', 'Larvicide', 'Routine', 'Location'], 'condition' => 'in'];
        } elseif (Auth::user()->user_type == TECHNICIAN) {
            $wherecondition[] = ['column' => 'tb.to_id', 'operator' => '', 'value' => $user_id, 'condition' => 'and'];
        } elseif (Auth::user()->user_type == STORE_KEEPER) {
            $wherecondition[] = ['column' => 'tb.to_id', 'operator' => '', 'value' => STORE_KEEPER, 'condition' => 'and'];
        } elseif (Auth::user()->user_type == MOSQUITO_CONTROL) {
            $wherecondition[] = ['column' => 'tb.to_id', 'operator' => '', 'value' => MOSQUITO_CONTROL, 'condition' => 'and'];
        }

        return $wherecondition;
    }

    /**
     * Get empty notification HTML
     */
    private function getEmptyNotificationHtml()
    {
        return '<a href="" class="text-reset notification-item">
            <div class="d-flex">
                <div class="avatar-xs me-3">
                    <span class="avatar-title bg-primary rounded-circle font-size-16">
                        <i class="ri-notification-3-fill"></i>
                    </span>
                </div>
                <div class="flex-1">
                    <div class="font-size-12 text-muted">
                        <p class="mb-1">No Notifications Found</p>                                
                    </div>
                </div>
            </div>
        </a>';
    }
}
