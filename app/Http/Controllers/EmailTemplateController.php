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

class EmailTemplateController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'email_templates';

    /**
     * Display email template create/edit form
     */
    public function email_templates($id = '')
    {
        $res = permissionexists($this->module);
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Email Template' : 'Create Email Template';

        $data = [
            'email' => '',
            'status' => Common_model::getDataFromTable(
                'tbl_status',
                '*',
                ['type' => 'Default'],
                '',
                'id',
                'ASC',
                '',
                0,
                true,
                ''
            ),
            'backURL' => 'email_templates_list'
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $emailData = Common_model::getDataFromTable(
                    'tbl_emailtemplates',
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

                if (!empty($emailData) && is_array($emailData) && isset($emailData[0])) {
                    $emailRecord = $emailData[0];
                    if (isset($emailRecord['id'])) {
                        $emailRecord['email_id'] = $emailRecord['id'];
                    }
                    $data['email'] = $emailRecord;
                } else {
                    Log::warning('Email template not found for edit. Decrypted ID: ' . $decryptedId);
                    $data['email'] = '';
                }
            } catch (\Exception $e) {
                Log::error('Error decrypting email template ID for edit: ' . $e->getMessage());
                $data['email'] = '';
            }
        }

        return view('master_pages.create_email_templates', compact('pageTitle', 'data'));
    }

    /**
     * Insert or update email template
     */
    public function insert_update_email_templates(Request $request)
    {
        try {
            $postData = array();
            $requestData = $request->post();
            
            // Check if data is wrapped in 'data' key (JSON form) or sent directly
            if (isset($requestData['data']) && !empty($requestData['data'])) {
                parse_str(json_decode($requestData['data'], 1), $postData);
            } else {
                // Data sent directly without wrapper
                $postData = $requestData;
            }

            $errMessage = $this->validateEmailTemplateData($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $email_id = $postData['email_id'] ?? $postData['id'] ?? null;
            $operation = ($email_id && $email_id != "") ? 'Update' : 'Add';

            $data = $this->prepareEmailTemplateData($postData, $operation);

            if ($operation == 'Add') {
                $result = $this->createEmailTemplate($data);
                $succ_msg = 'Email Template added successfully';
            } else {
                $result = $this->updateEmailTemplate($email_id, $data);
                $succ_msg = 'Email Template updated successfully';
            }

            if ($result) {
                $this->sendSuccessResponse($succ_msg, $operation, url('email_templates_list'));
            } else {
                $this->sendErrorResponse('Something went wrong, try again later', 1);
            }
        } catch (\Exception $e) {
            Log::error('Email Template Management Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Display email template list with gridview
     */
    public function email_templates_list(Request $request)
    {
        $res = permissionexists($this->module);
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'List of Email Templates';
        $statusOptions = $this->getStatusOptions('Default');

        $filters = [
            $this->buildTextFilter('search', 'Search by Template Name, Subject, Body', 'Search', 'col-md-3'),
            $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2')
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['Actions', '#', 'Template Name', 'Status', 'Created On', 'Updated On'],
            'table' => 'tbl_emailtemplates',
            'dataurl' => 'get_email_templates_list',
            'addurl' => 'email_templates/add',
            'filters' => $filters
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    /**
     * Get email template list data for DataTables
     */
    public function get_email_templates_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];

            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $indexColumn = 'tb.id';
            $selectColumns = ['tb.*', 'tt.status_name', 'tt.class'];
            $dataTableSortOrdering = ['', '', 'tb.template_name', 'tt.status_name', 'tb.created_on', 'tb.updated_on'];
            $table_name = "$table as tb";

            $joinsArray = [
                ['table_name' => 'tbl_status as tt', 'condition' => 'tt.id=tb.status', 'join_type' => 'left']
            ];

            $wherecondition = [];
            if (!empty($status) && $status != 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.template_name', 'tb.template_subject', 'tb.template_otheremails', 'tb.template_body'];
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
                $includeJoinInCountQuery = 1
            );

            $totalRecords = $getRecordListing['recordsTotal'];
            $recordsFiltered = $getRecordListing['recordsFiltered'];
            $recordListing = array();
            $i = 0;
            $srNumber = $start;

            if (!empty($getRecordListing['data'])) {
                foreach ($getRecordListing['data'] as $recordData) {
                    $j = 0;
                    $action = '<a href="' . url('email_templates/edit/' . Crypt::encrypt($recordData->id)) . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><i class="ri-edit-fill"></i></a>';
                    $recordListing[$i][$j++] = $this->wrapGridActions($action);
                    $recordListing[$i][$j++] = $srNumber + 1;
                    $recordListing[$i][$j++] = $recordData->template_name;
                    $recordListing[$i][$j++] = "<label class='$recordData->class'>" . $recordData->status_name . "</label>";
                    $recordListing[$i][$j++] = displayCustomDateTime($recordData->created_on);
                    $recordListing[$i][$j++] = displayCustomDateTime($recordData->updated_on);
                    $i++;
                    $srNumber++;
                }
                $final_data = json_encode($recordListing);
            } else {
                $final_data = '[]';
            }

            echo '{"draw":' . $draw . ',"recordsTotal":' . $recordsFiltered . ',"recordsFiltered":' . $recordsFiltered . ',"data":' . $final_data . '}';
        } catch (\Exception $e) {
            Log::error('Get Email Templates List Error: ' . $e->getMessage());
            Log::error($e);
            echo '{"draw":' . ($request->draw ?? 0) . ',"recordsTotal":0,"recordsFiltered":0,"data":[]}';
        }
    }

    /**
     * Validate email template data
     */
    private function validateEmailTemplateData($postData)
    {
        $errMessage = '';

        // Ensure $postData is an array
        if (!is_array($postData)) {
            return '<li>Invalid data format</li>';
        }

        // Check mandatory fields
        if (empty(trim($postData['template_name'] ?? ''))) {
            $errMessage .= '<li>Please Enter Template Name</li>';
        }

        if (empty(trim($postData['template_subject'] ?? ''))) {
            $errMessage .= '<li>Please Enter Template Subject</li>';
        }

        if (empty(trim($postData['template_body'] ?? ''))) {
            $errMessage .= '<li>Please Enter Template Body</li>';
        }

        // Check template name uniqueness only if template_name exists
        if (!empty($postData['template_name'] ?? '')) {
            $email_id = $postData['email_id'] ?? $postData['id'] ?? null;
            $templateExists = Common_model::check_exists(
                'tbl_emailtemplates',
                'template_name',
                $postData['template_name'],
                $email_id ? 'id' : '',
                $email_id ? [$email_id] : []
            );

            if ($templateExists !== false && $templateExists > 0) {
                $errMessage .= '<li>Email Template with this Name Already Exists</li>';
            }
        }

        // Validate template_otheremails if provided (comma-separated emails)
        if (!empty($postData['template_otheremails'] ?? '')) {
            $emails = explode(',', $postData['template_otheremails']);
            foreach ($emails as $email) {
                $email = trim($email);
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errMessage .= '<li>Please enter valid email addresses in "Send to other Emails" field (comma-separated)</li>';
                    break;
                }
            }
        }

        return $errMessage;
    }

    /**
     * Prepare email template data for insert/update
     */
    private function prepareEmailTemplateData($postData, $operation)
    {
        unset($postData['_token']);
        unset($postData['email_id']);
        unset($postData['id']);

        $currentUserId = Auth::user()->id ?? Auth::user()->user_id ?? null;

        if ($operation == 'Add') {
            $postData['status'] = ACTIVE;
            $postData['created_by'] = $currentUserId;
            $postData['created_on'] = current_datetime();
        } else {
            $postData['updated_by'] = $currentUserId;
            $postData['updated_on'] = current_datetime();
        }

        // Trim string fields
        if (isset($postData['template_name'])) {
            $postData['template_name'] = trim($postData['template_name']);
        }
        if (isset($postData['template_subject'])) {
            $postData['template_subject'] = trim($postData['template_subject']);
        }
        if (isset($postData['template_body'])) {
            $postData['template_body'] = trim($postData['template_body']);
        }
        if (isset($postData['template_otheremails'])) {
            $postData['template_otheremails'] = trim($postData['template_otheremails']);
        }

        return $postData;
    }

    /**
     * Create new email template
     */
    private function createEmailTemplate($data)
    {
        try {
            return Common_model::addDataIntoTable('tbl_emailtemplates', $data);
        } catch (\Exception $e) {
            Log::error('Create Email Template Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing email template
     */
    private function updateEmailTemplate($email_id, $data)
    {
        try {
            return Common_model::updateDataFromTable('tbl_emailtemplates', $data, 'id', $email_id);
        } catch (\Exception $e) {
            Log::error('Update Email Template Error: ' . $e->getMessage());
            return false;
        }
    }
}
