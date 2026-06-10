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

class DelayCategoriesController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'delay_categories';

    protected AuditTrailService $auditTrail;

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function delay_category_form(Request $request, $id = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Delay Category' : 'Create Delay Category';
        $data = ['category' => ''];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_delay_categories',
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
                    $record['category_id'] = $record['id'];
                    $data['category'] = $record;
                }
            } catch (\Exception $e) {
                Log::error('Delay category edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_categories.create-delay-category-form', compact('pageTitle', 'data'));
        }

        return view('delay_categories.create-delay-category', compact('pageTitle', 'data'));
    }

    public function insert_update_delay_category(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            $this->sendErrorResponse('Permission missing. Contact administrator.', 1);
            return;
        }

        try {
            $requestData = $request->post();
            parse_str(json_decode($requestData['data'], true), $postData);

            $errMessage = $this->validateCategoryData($postData);
            if ($errMessage !== '') {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $categoryId = $postData['category_id'] ?? null;
            $operation = ($categoryId && $categoryId !== '') ? 'Update' : 'Add';
            $payload = $this->prepareCategoryData($postData, $operation);

            if ($operation === 'Add') {
                $newId = Common_model::addDataIntoTable('tbl_delay_categories', $payload);
                if ($newId) {
                    $this->auditTrail->log('delay_category', (int) $newId, 'create', null, $payload);
                    $this->sendSuccessResponse('Delay category added successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            } else {
                $old = Common_model::getDataFromTable('tbl_delay_categories', '*', ['id' => $categoryId], '', '', 'ASC', '', 0, true, '');
                $result = Common_model::updateDataFromTable('tbl_delay_categories', $payload, 'id', $categoryId);
                if ($result) {
                    $this->auditTrail->log('delay_category', (int) $categoryId, 'update', $old[0] ?? null, $payload);
                    $this->sendSuccessResponse('Delay category updated successfully', $operation);
                } else {
                    $this->sendErrorResponse('Something went wrong, try again later', 1);
                }
            }
        } catch (\Exception $e) {
            Log::error('Delay Category Error: ' . $e->getMessage());
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    public function delay_category_list(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'Delay Categories';
        $statusOptions = [
            ['value' => ACTIVE, 'label' => 'Active'],
            ['value' => INACTIVE, 'label' => 'Inactive'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Category Name', 'Primary Delay Driver', 'Status', 'Created On', 'Actions'],
            'table' => 'tbl_delay_categories',
            'dataurl' => 'get_delay_category_list',
            'addurl' => 'delay-categories/add',
            'addurllabel' => 'Add Delay Category',
            'filters' => [
                $this->buildTextFilter('search', 'Search category', 'Search', 'col-md-3'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'Select status', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_delay_category_list(Request $request)
    {
        try {
            $table = $request->table;
            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($status) && $status !== 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.category_name', 'tb.description'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*'],
                ['', 'tb.category_name', 'tb.description', 'tb.status', 'tb.created_on'],
                "$table as tb",
                [],
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
                $editUrl = getProjectUrl('delay-categories/edit/' . $id);
                $desc = $recordData->description ?? '';
                if (strlen($desc) > 80) {
                    $desc = substr($desc, 0, 80) . '…';
                }

                $recordListing[] = [
                    $srNumber + 1,
                    e($recordData->category_name),
                    e($desc),
                    $this->formatStatusBadge($recordData->status),
                    displayCustomDateTime($recordData->created_on),
                    '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Delay Category\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a>',
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
            Log::error('Get Delay Category List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateCategoryData(array $postData): string
    {
        $errMessage = '';
        $name = trim($postData['category_name'] ?? '');
        if ($name === '') {
            $errMessage .= '<li>Please enter category name</li>';
        }

        $categoryId = $postData['category_id'] ?? null;
        if ($name !== '') {
            $query = DB::table('tbl_delay_categories')
                ->where('category_name', $name)
                ->where('is_delete', 0);
            if ($categoryId) {
                $query->where('id', '!=', $categoryId);
            }
            if ($query->exists()) {
                $errMessage .= '<li>Category name already exists</li>';
            }
        }

        return $errMessage;
    }

    private function prepareCategoryData(array $postData, string $operation): array
    {
        $userId = Auth::id();
        $data = [
            'category_name' => trim($postData['category_name']),
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
