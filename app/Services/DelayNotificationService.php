<?php

namespace App\Services;

use App\Modules\InAppNotifications\Services\InAppNotificationService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * PDTS domain notifier — delay logged → notify project + department SPOCs.
 * Uses the portable InAppNotifications module; copy this pattern for other domains.
 */
class DelayNotificationService
{
    protected InAppNotificationService $notifications;

    public function __construct(InAppNotificationService $notifications)
    {
        $this->notifications = $notifications;
    }

    public function notifyDelayLogged(int $delayRegisterId, int $loggedByUserId): void
    {
        if ($delayRegisterId <= 0 || !Schema::hasTable('tbl_delay_registers')) {
            return;
        }

        $delay = DB::table('tbl_delay_registers as dr')
            ->join('tbl_projects as tp', 'tp.id', '=', 'dr.project_id')
            ->where('dr.id', $delayRegisterId)
            ->where('dr.is_delete', 0)
            ->where('tp.is_delete', 0)
            ->first([
                'dr.id',
                'dr.project_id',
                'dr.project_department_id',
                'dr.delay_title',
                'dr.severity',
                'dr.delay_days',
                'tp.project_code',
                'tp.project_name',
                'tp.responsible_user_id',
            ]);

        if (!$delay) {
            return;
        }

        $projectId = (int) $delay->project_id;
        $pdId = (int) ($delay->project_department_id ?? 0);
        $departmentName = $this->resolveDepartmentName($pdId);
        $loggedByName = $this->resolveUserName($loggedByUserId);

        $title = 'Delay: ' . $departmentName . ' — ' . trim(($delay->project_code ?? '') . ' — ' . ($delay->project_name ?? ''));
        $message = trim(($delay->delay_title ?? 'Delay logged')
            . ' (' . ucfirst((string) ($delay->severity ?? 'minor'))
            . ($delay->delay_days ? ', ' . (int) $delay->delay_days . ' days' : '') . ')'
            . ($loggedByName !== '' ? ' — logged by ' . $loggedByName : ''));

        $recipientIds = $this->resolveRecipientUserIds($projectId, $loggedByUserId);
        if ($recipientIds === []) {
            return;
        }

        $projectSpocId = (int) ($delay->responsible_user_id ?? 0);

        foreach ($recipientIds as $recipientId) {
            $action = $this->buildActionForRecipient($recipientId, $projectId, $pdId, $projectSpocId);

            $notificationId = $this->notifications->notifyUser($recipientId, 'delay_logged', $title, $message, [
                'entity_type' => 'delay_register',
                'entity_id' => $delayRegisterId,
                'meta' => [
                    'project_id' => $projectId,
                    'project_department_id' => $pdId ?: null,
                    'delay_register_id' => $delayRegisterId,
                ],
                'triggered_by' => $loggedByUserId,
                'action_url' => $action['url'],
                'action_mode' => $action['mode'],
            ]);

            if (!$notificationId) {
                Log::warning('Delay notification not created', [
                    'delay_register_id' => $delayRegisterId,
                    'recipient_id' => $recipientId,
                ]);
            }
        }
    }

    /**
     * @return int[]
     */
    public function resolveRecipientUserIds(int $projectId, int $excludeUserId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        $ids = [];

        $projectSpocId = (int) (DB::table('tbl_projects')
            ->where('id', $projectId)
            ->where('is_delete', 0)
            ->value('responsible_user_id') ?? 0);
        if ($projectSpocId > 0) {
            $ids[] = $projectSpocId;
        }

        if (Schema::hasTable('tbl_project_departments')) {
            $deptSpocIds = DB::table('tbl_project_departments')
                ->where('project_id', $projectId)
                ->where('is_delete', 0)
                ->whereNotNull('spoc_user_id')
                ->where('spoc_user_id', '>', 0)
                ->pluck('spoc_user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_merge($ids, $deptSpocIds);
        }

        return $this->notifications->filterActiveUserIds($ids, $excludeUserId);
    }

    private function buildActionForRecipient(int $recipientId, int $projectId, int $delayedPdId, int $projectSpocId): array
    {
        if ($projectSpocId > 0 && $recipientId === $projectSpocId) {
            return [
                'url' => 'projects/wizard/' . Crypt::encrypt($projectId) . '?step=execution',
                'mode' => 'redirect',
            ];
        }

        if ($delayedPdId > 0 && Schema::hasTable('tbl_project_departments')) {
            $delayedRow = DB::table('tbl_project_departments')
                ->where('id', $delayedPdId)
                ->where('is_delete', 0)
                ->first(['spoc_user_id']);

            if ($delayedRow && (int) ($delayedRow->spoc_user_id ?? 0) === $recipientId) {
                return [
                    'url' => 'spoc-tasks/view/' . Crypt::encrypt($delayedPdId),
                    'mode' => 'sidelayout',
                ];
            }
        }

        // Other department SPOCs (e.g. MEP when Civil logged delay): open notifications list.
        return [
            'url' => (string) config('in_app_notifications.routes.list', 'in-app-notifications/list'),
            'mode' => 'redirect',
        ];
    }

    private function resolveDepartmentName(int $projectDepartmentId): string
    {
        if ($projectDepartmentId <= 0 || !Schema::hasTable('tbl_project_departments')) {
            return 'Department';
        }

        $deptTable = Schema::hasTable('tbl_departments') ? 'tbl_departments' : 'tbl_delay_categories';
        $nameCol = Schema::hasColumn($deptTable, 'department_name') ? 'department_name' : 'category_name';

        $row = DB::table('tbl_project_departments as pd')
            ->join("$deptTable as d", 'd.id', '=', 'pd.department_id')
            ->where('pd.id', $projectDepartmentId)
            ->where('pd.is_delete', 0)
            ->first(["d.$nameCol as department_name"]);

        return trim((string) ($row->department_name ?? 'Department'));
    }

    private function resolveUserName(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        $usersTable = (string) config('in_app_notifications.users_table', 'tbl_user');
        $user = DB::table($usersTable)
            ->where('id', $userId)
            ->where('is_delete', 0)
            ->first(['first_name', 'last_name']);

        if (!$user) {
            return '';
        }

        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
    }
}
