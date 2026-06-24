<?php

namespace App\Modules\InAppNotifications\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\GridConfigTrait;
use App\Http\Traits\WebResponseTrait;
use App\Models\Datatables_model;
use App\Modules\InAppNotifications\Services\InAppNotificationService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InAppNotificationController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    protected InAppNotificationService $notifications;

    public function __construct(InAppNotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        $pageTitle = 'My Notifications';
        $statusOptions = [
            ['value' => (string) config('in_app_notifications.status_unread', 0), 'label' => 'Unread'],
            ['value' => (string) config('in_app_notifications.status_read', 1), 'label' => 'Read'],
        ];

        $grid_data = $this->buildGridConfig([
            'columns' => ['#', 'Title', 'Message', 'Type', 'Status', 'Received On'],
            'table' => config('in_app_notifications.table', 'tbl_user_in_app_notifications'),
            'dataurl' => 'get_in_app_notification_list',
            'no_sort_columns' => ['#', 'Message'],
            'filters' => [
                $this->buildTextFilter('search', 'Search title or message', 'Search', 'col-md-4'),
                $this->buildSelectFilter('status_filter', $statusOptions, 'Status', 'All statuses', true, true, 'col-md-2'),
            ],
        ]);

        return view('gridviews.gridviews', compact('pageTitle', 'grid_data'));
    }

    public function getList(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            if ($userId <= 0) {
                return response()->json([
                    'draw' => (int) ($request->draw ?? 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                ]);
            }

            $draw = $request->draw;
            $start = $request->start;
            $filters = $request->filters ?? [];
            $search = $filters['search'] ?? '';
            $status = $filters['status_filter'] ?? '';

            $table = config('in_app_notifications.table', 'tbl_user_in_app_notifications');
            $unread = (int) config('in_app_notifications.status_unread', 0);
            $read = (int) config('in_app_notifications.status_read', 1);

            $wherecondition = [
                ['column' => 'tb.is_delete', 'operator' => '', 'value' => 0, 'condition' => 'and'],
                ['column' => 'tb.user_id', 'operator' => '', 'value' => $userId, 'condition' => 'and'],
            ];

            if ($status !== '' && $status !== 'All') {
                $wherecondition[] = ['column' => 'tb.status', 'operator' => '', 'value' => (int) $status, 'condition' => 'and'];
            }

            $searchColumns = [];
            $search_param = '';
            if (!empty($search)) {
                $searchColumns = ['tb.title', 'tb.message', 'tb.notification_type'];
                $search_param = $search;
            }

            $getRecordListing = Datatables_model::getDataTableResult(
                ['tb.id', 'tb.title', 'tb.message', 'tb.notification_type', 'tb.status', 'tb.created_on'],
                ['', 'tb.title', 'tb.message', 'tb.notification_type', 'tb.status', 'tb.created_on'],
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

            $recordListing = [];
            $srNumber = (int) $start;

            foreach ($getRecordListing['data'] as $recordData) {
                $srNumber++;
                $isUnread = (int) ($recordData->status ?? 0) === $unread;
                $statusLabel = $isUnread
                    ? '<span class="badge bg-warning text-dark">Unread</span>'
                    : '<span class="badge bg-secondary">Read</span>';

                $message = (string) ($recordData->message ?? '');
                if (strlen($message) > 120) {
                    $message = substr($message, 0, 120) . '…';
                }

                $recordListing[] = [
                    $srNumber,
                    e($recordData->title ?? ''),
                    e($message),
                    e(ucfirst(str_replace('_', ' ', (string) ($recordData->notification_type ?? '')))),
                    $statusLabel,
                    displayCustomDateTime($recordData->created_on ?? ''),
                ];
            }

            return response()->json([
                'draw' => (int) $draw,
                'recordsTotal' => (int) $getRecordListing['recordsTotal'],
                'recordsFiltered' => (int) $getRecordListing['recordsFiltered'],
                'data' => $recordListing,
            ]);
        } catch (\Exception $e) {
            Log::error('In-app notification list: ' . $e->getMessage());

            return response()->json([
                'draw' => (int) ($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function poll(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            if ($userId <= 0) {
                echo json_encode([
                    'error' => 1,
                    'msg' => [0 => 'Unauthorized'],
                    'unread_count' => 0,
                    'notifications' => [],
                ]);

                return;
            }

            $incremental = $request->has('since_id');
            $sinceId = $incremental ? (int) $request->input('since_id', 0) : null;
            $result = $this->notifications->poll($userId, $sinceId, $incremental);

            echo json_encode(array_merge(['error' => 0], $result));
        } catch (\Exception $e) {
            Log::error('In-app notification poll: ' . $e->getMessage());
            echo json_encode([
                'error' => 2,
                'msg' => [0 => 'Unable to load notifications'],
                'unread_count' => 0,
                'notifications' => [],
            ]);
        }
    }

    public function markRead(Request $request)
    {
        try {
            $userId = (int) Auth::id();
            $notificationId = (int) ($request->input('notification_id') ?? 0);

            if ($notificationId <= 0 && $request->filled('data')) {
                $decoded = json_decode((string) $request->input('data'), true);
                if (is_array($decoded)) {
                    $notificationId = (int) ($decoded['notification_id'] ?? 0);
                }
            }

            if ($userId <= 0 || $notificationId <= 0) {
                $this->sendValidationErrorResponse('Invalid notification');

                return;
            }

            $this->notifications->markRead($notificationId, $userId);

            echo json_encode([
                'error' => 0,
                'unread_count' => $this->notifications->unreadCount($userId),
            ]);
        } catch (\Exception $e) {
            Log::error('In-app notification mark read: ' . $e->getMessage());
            $this->sendErrorResponse('Unable to update notification');
        }
    }
}