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

class DelayAttachmentsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'delay_attachments';

    protected AuditTrailService $auditTrail;

    private const UPLOAD_DIR = 'uploads/delay_attachments';
    private const MAX_FILE_SIZE_KB = 10240;
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    public function __construct(AuditTrailService $auditTrail)
    {
        $this->auditTrail = $auditTrail;
    }

    public function attachment_add(Request $request, $delayRegisterId = '')
    {
        return $this->attachment_form($request, '', $delayRegisterId);
    }

    public function attachment_edit(Request $request, $id)
    {
        return $this->attachment_form($request, $id, '');
    }

    public function attachment_form(Request $request, $id = '', $delayRegisterId = '')
    {
        if (!modulePermissionExists($this->module)) {
            if ($request->input('postKey') == 'sidelayoutContent') {
                return response()->json(['error' => 1, 'msg' => 'You dont have permission to access this page']);
            }
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = $id ? 'Edit Attachment' : 'Upload Attachment';
        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $data = [
            'attachment' => '',
            'delay_registers' => $this->getDelayRegisterOptions(),
            'attachment_types' => $this->getAttachmentTypes(),
            'preset_delay_register_id' => $presetDelayId,
            'panel_reload_url' => '',
        ];

        if ($id) {
            try {
                $decryptedId = Crypt::decrypt($id);
                $rows = Common_model::getDataFromTable(
                    'tbl_delay_attachments',
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
                    $record['attachment_id'] = $record['id'];
                    $data['attachment'] = $record;
                    $presetDelayId = (int) $record['delay_register_id'];
                    $data['preset_delay_register_id'] = $presetDelayId;
                }
            } catch (\Exception $e) {
                Log::error('Attachment edit decrypt error: ' . $e->getMessage());
            }
        }

        if ($presetDelayId) {
            $data['panel_reload_url'] = getProjectUrl('delay-attachments/panel/' . Crypt::encrypt($presetDelayId));
        }

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_attachments.create-delay-attachment-form', compact('pageTitle', 'data'));
        }

        return view('delay_attachments.create-delay-attachment', compact('pageTitle', 'data'));
    }

    public function attachment_panel(Request $request, $delayRegisterId)
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
        $pageTitle = 'Attachments';
        $data = [
            'delay' => $delay,
            'delay_register_id' => $delayId,
            'encrypted_delay_id' => $encryptedDelayId,
            'add_url' => getProjectUrl('delay-attachments/add/' . $encryptedDelayId),
            'panel_url' => getProjectUrl('delay-attachments/panel/' . $encryptedDelayId),
        ];
        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Type', 'File', 'Description', 'Size', 'Uploaded', 'Actions'],
            'table' => 'tbl_delay_attachments',
            'dataurl' => 'get_delay_attachment_list',
            'page_length' => 10,
        ]);

        if ($request->input('postKey') == 'sidelayoutContent') {
            return view('delay_attachments.attachment-panel', compact('pageTitle', 'data', 'grid_data'));
        }

        return redirect(getProjectUrl('delay-attachments-list/' . $encryptedDelayId));
    }

    public function insert_update_delay_attachment(Request $request)
    {
        if (!modulePermissionExists($this->module)) {
            return response()->json(['error' => 1, 'msg' => 'Permission missing. Contact administrator.']);
        }

        try {
            $postData = $request->all();
            $attachmentId = $postData['attachment_id'] ?? null;
            $operation = ($attachmentId && $attachmentId !== '') ? 'Update' : 'Add';

            $errMessage = $this->validateAttachmentData($postData, $operation, $request);
            if ($errMessage !== '') {
                return response()->json(['error' => 1, 'msg' => $errMessage]);
            }

            $payload = $this->prepareAttachmentData($postData, $operation);
            $oldFilePath = null;

            if ($request->hasFile('attachment_file')) {
                $upload = $this->storeUploadedFile($request->file('attachment_file'));
                if ($upload['error']) {
                    return response()->json(['error' => 1, 'msg' => $upload['msg']]);
                }
                if ($operation === 'Update' && $attachmentId) {
                    $oldFilePath = DB::table('tbl_delay_attachments')->where('id', $attachmentId)->value('file_path');
                }
                $payload['file_name'] = $upload['file_name'];
                $payload['file_path'] = $upload['file_path'];
                $payload['mime_type'] = $upload['mime_type'];
                $payload['file_size'] = $upload['file_size'];
            } elseif ($operation === 'Add') {
                return response()->json(['error' => 1, 'msg' => 'Please select a file to upload']);
            }

            if ($operation === 'Add') {
                $payload['uploaded_by'] = Auth::id();
                $payload['uploaded_on'] = current_datetime();
                $newId = Common_model::addDataIntoTable('tbl_delay_attachments', $payload);
                if ($newId) {
                    $this->auditTrail->log('delay_attachment', (int) $newId, 'create', null, $payload);
                    return response()->json(['error' => 0, 'msg' => 'Attachment uploaded successfully']);
                }
                return response()->json(['error' => 1, 'msg' => 'Something went wrong, try again later']);
            }

            $old = Common_model::getDataFromTable('tbl_delay_attachments', '*', ['id' => $attachmentId], '', '', 'ASC', '', 0, true, '');
            $result = Common_model::updateDataFromTable('tbl_delay_attachments', $payload, 'id', $attachmentId);
            if ($result) {
                if ($oldFilePath && !empty($payload['file_path'])) {
                    $this->removeStoredFile($oldFilePath);
                }
                $this->auditTrail->log('delay_attachment', (int) $attachmentId, 'update', $old[0] ?? null, $payload);
                return response()->json(['error' => 0, 'msg' => 'Attachment updated successfully']);
            }

            return response()->json(['error' => 1, 'msg' => 'Something went wrong, try again later']);
        } catch (\Exception $e) {
            Log::error('Delay Attachment Error: ' . $e->getMessage());
            return response()->json(['error' => 2, 'msg' => $e->getMessage()]);
        }
    }

    public function attachment_list(Request $request, $delayRegisterId = '')
    {
        if (!modulePermissionExists($this->module)) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $presetDelayId = $this->resolveDelayRegisterId($delayRegisterId);
        $pageTitle = 'Delay Attachments';
        $typeOptions = array_map(fn ($t) => ['value' => $t['value'], 'label' => $t['label']], $this->getAttachmentTypes());
        $delayOptions = $this->getDelayRegisterOptions();

        $delayFilter = $this->buildSelectFilter('delay_register_id', $delayOptions, 'Delay Entry', 'All delays', true, true, 'col-md-3');
        if ($presetDelayId) {
            $delayFilter['selected_value'] = $presetDelayId;
        }

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Project', 'Delay Title', 'Type', 'File', 'Description', 'Uploaded', 'Actions'],
            'table' => 'tbl_delay_attachments',
            'dataurl' => 'get_delay_attachment_list',
            'addurl' => $presetDelayId
                ? 'delay-attachments/add/' . Crypt::encrypt($presetDelayId)
                : 'delay-attachments/add',
            'addurllabel' => 'Upload Attachment',
            'filters' => [
                $this->buildTextFilter('search', 'Search file name or description', 'Search', 'col-md-3'),
                $delayFilter,
                $this->buildSelectFilter('attachment_type', $typeOptions, 'Type', 'All types', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function get_delay_attachment_list(Request $request)
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
            $attachmentType = $filters['attachment_type'] ?? '';

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
            ];
            if (!empty($delayRegisterId) && $delayRegisterId !== 'All') {
                $wherecondition[] = ['column' => 'tb.delay_register_id', 'operator' => '', 'value' => $delayRegisterId, 'condition' => 'and'];
            }
            if (!empty($attachmentType) && $attachmentType !== 'All') {
                $wherecondition[] = ['column' => 'tb.attachment_type', 'operator' => '', 'value' => $attachmentType, 'condition' => 'and'];
            }

            $joinsArray = [
                ['table_name' => 'tbl_delay_registers as dr', 'condition' => 'dr.id=tb.delay_register_id', 'join_type' => 'left'],
                ['table_name' => 'tbl_projects as tp', 'condition' => 'tp.id=dr.project_id', 'join_type' => 'left'],
            ];

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.file_name', 'tb.description', 'dr.delay_title', 'tp.project_code', 'tp.project_name'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.*', 'dr.delay_title', 'tp.project_code', 'tp.project_name'],
                ['', 'tb.attachment_type', 'tb.file_name', 'tb.description', 'tb.file_size', 'tb.uploaded_on'],
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
                $editUrl = getProjectUrl('delay-attachments/edit/' . $id);
                $fileUrl = getImageUrl($recordData->file_path ?? '');
                $fileLabel = e($recordData->file_name ?? '');
                $fileLink = $fileUrl
                    ? '<a href="' . e($fileUrl) . '" target="_blank" rel="noopener">' . $fileLabel . '</a>'
                    : $fileLabel;
                $typeLabel = $this->formatAttachmentTypeLabel($recordData->attachment_type);
                $sizeLabel = $this->formatFileSize($recordData->file_size);
                $uploaded = !empty($recordData->uploaded_on) ? displayCustomDateTime($recordData->uploaded_on) : '';
                $deleteAction = '<a href="javascript:void(0)" onclick="deleteRecord(\'' . $id . '\',1,\'tbl_delay_attachments\',\'' . ($isPanel ? 'attachmentPanelTable' : 'ucList') . '\'); return false;" title="Delete"><i class="ri-delete-bin-fill text-danger"></i></a>';

                if ($isPanel) {
                    $recordListing[] = [
                        $srNumber + 1,
                        e($typeLabel),
                        $fileLink,
                        e($this->truncateText($recordData->description ?? '', 60)),
                        $sizeLabel,
                        $uploaded,
                        '<a href="' . e($fileUrl) . '" target="_blank" rel="noopener" title="View"><i class="ri-eye-fill"></i></a> '
                        . '<a href="javascript:void(0)" onclick="openAttachmentEdit(\'' . $editUrl . '\'); return false;" title="Edit"><i class="ri-edit-fill"></i></a> '
                        . $deleteAction,
                    ];
                } else {
                    $projectLabel = trim(($recordData->project_code ?? '') . ' — ' . ($recordData->project_name ?? ''));
                    $recordListing[] = [
                        $srNumber + 1,
                        e($projectLabel),
                        e($recordData->delay_title ?? ''),
                        e($typeLabel),
                        $fileLink,
                        e($this->truncateText($recordData->description ?? '', 60)),
                        $uploaded,
                        '<a href="' . e($fileUrl) . '" target="_blank" rel="noopener" title="View"><i class="ri-eye-fill"></i></a> '
                        . '<a href="javascript:void(0)" onclick="openSideLayout({}, \'' . $editUrl . '\', \'Edit Attachment\', 85); return false;" title="Edit"><i class="ri-edit-fill"></i></a> '
                        . $deleteAction,
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
            Log::error('Get Attachment List Error: ' . $e->getMessage());
            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    private function validateAttachmentData(array $postData, string $operation, Request $request): string
    {
        $errMessage = '';
        if (empty($postData['delay_register_id'])) {
            $errMessage .= '<li>Please select a delay entry</li>';
        }
        if (empty($postData['attachment_type'])) {
            $errMessage .= '<li>Please select attachment type</li>';
        }
        if ($operation === 'Add' && !$request->hasFile('attachment_file')) {
            $errMessage .= '<li>Please select a file to upload</li>';
        }
        return $errMessage;
    }

    private function prepareAttachmentData(array $postData, string $operation): array
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
            'attachment_type' => $postData['attachment_type'],
            'description' => trim($postData['description'] ?? ''),
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

    private function storeUploadedFile($file): array
    {
        if (!$file || !$file->isValid()) {
            return ['error' => true, 'msg' => 'Invalid file upload'];
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return ['error' => true, 'msg' => 'File type not allowed. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)];
        }

        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = (int) $file->getSize();

        if ($fileSize > self::MAX_FILE_SIZE_KB * 1024) {
            return ['error' => true, 'msg' => 'File size must be 10 MB or less'];
        }

        $directory = public_path(self::UPLOAD_DIR);
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            return ['error' => true, 'msg' => 'Unable to create upload directory'];
        }

        $storedName = time() . '_' . uniqid() . '.' . $extension;
        if (!$file->move($directory, $storedName)) {
            return ['error' => true, 'msg' => 'Failed to save uploaded file'];
        }

        return [
            'error' => false,
            'file_name' => $fileName,
            'file_path' => self::UPLOAD_DIR . '/' . $storedName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ];
    }

    private function removeStoredFile(?string $filePath): void
    {
        if (empty($filePath)) {
            return;
        }
        $absolute = function_exists('getUploadAbsolutePath') ? getUploadAbsolutePath($filePath) : public_path($filePath);
        if ($absolute && is_file($absolute)) {
            @unlink($absolute);
        }
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

    private function getAttachmentTypes(): array
    {
        return [
            ['value' => 'photo', 'label' => 'Photo'],
            ['value' => 'drawing', 'label' => 'Drawing'],
            ['value' => 'noc', 'label' => 'NOC'],
            ['value' => 'approval_letter', 'label' => 'Approval Letter'],
            ['value' => 'vendor_communication', 'label' => 'Vendor Communication'],
            ['value' => 'change_order', 'label' => 'Change Order'],
            ['value' => 'other', 'label' => 'Other'],
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

    private function formatAttachmentTypeLabel(?string $type): string
    {
        foreach ($this->getAttachmentTypes() as $item) {
            if ($item['value'] === $type) {
                return $item['label'];
            }
        }
        return ucfirst(str_replace('_', ' ', (string) $type));
    }

    private function formatFileSize($bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    private function truncateText(?string $text, int $length): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }
        if (strlen($text) > $length) {
            return substr($text, 0, $length) . '…';
        }
        return $text;
    }
}
