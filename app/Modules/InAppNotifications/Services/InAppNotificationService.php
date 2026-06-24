<?php

namespace App\Modules\InAppNotifications\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InAppNotificationService
{
    public function table(): string
    {
        return (string) config('in_app_notifications.table', 'tbl_user_in_app_notifications');
    }

    protected function unreadStatus(): int
    {
        return (int) config('in_app_notifications.status_unread', 0);
    }

    protected function readStatus(): int
    {
        return (int) config('in_app_notifications.status_read', 1);
    }

    /**
     * Send one in-app notification to a user.
     *
     * @param  array<string, mixed>  $options  action_url, action_mode, entity_type, entity_id, meta, triggered_by
     */
    public function notifyUser(int $userId, string $type, string $title, string $message = '', array $options = []): ?int
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->create(array_merge($options, [
            'user_id' => $userId,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
        ]));
    }

    /**
     * @param  int[]  $userIds
     * @param  array<string, mixed>  $options
     * @return int[] Created notification ids
     */
    public function notifyUsers(array $userIds, string $type, string $title, string $message = '', array $options = []): array
    {
        $ids = [];
        foreach (array_unique(array_filter(array_map('intval', $userIds))) as $userId) {
            if ($userId <= 0) {
                continue;
            }
            $id = $this->notifyUser($userId, $type, $title, $message, $options);
            if ($id) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ?int
    {
        if (!Schema::hasTable($this->table())) {
            return null;
        }

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $meta = $data['meta'] ?? $data['meta_json'] ?? null;
        if (is_array($meta)) {
            $meta = json_encode($meta);
        }

        $actorId = $data['triggered_by'] ?? Auth::id();
        $now = function_exists('current_datetime') ? current_datetime() : now()->format('Y-m-d H:i:s');

        $row = [
            'user_id' => $userId,
            'notification_type' => (string) ($data['notification_type'] ?? 'general'),
            'title' => (string) ($data['title'] ?? 'Notification'),
            'message' => (string) ($data['message'] ?? ''),
            'entity_type' => !empty($data['entity_type']) ? (string) $data['entity_type'] : null,
            'entity_id' => !empty($data['entity_id']) ? (int) $data['entity_id'] : null,
            'meta_json' => $meta ?: null,
            'triggered_by' => $actorId ? (int) $actorId : null,
            'action_url' => !empty($data['action_url']) ? (string) $data['action_url'] : null,
            'action_mode' => (string) ($data['action_mode'] ?? 'redirect'),
            'status' => $this->unreadStatus(),
            'created_by' => Auth::id(),
            'created_on' => $now,
            'updated_by' => Auth::id(),
            'updated_on' => $now,
            'is_delete' => 0,
        ];

        return (int) DB::table($this->table())->insertGetId($row);
    }

    public function unreadCount(int $userId): int
    {
        if (!Schema::hasTable($this->table()) || $userId <= 0) {
            return 0;
        }

        return (int) DB::table($this->table())
            ->where('user_id', $userId)
            ->where('status', $this->unreadStatus())
            ->where('is_delete', 0)
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentUnread(int $userId, ?int $limit = null): array
    {
        if (!Schema::hasTable($this->table()) || $userId <= 0) {
            return [];
        }

        $limit = $limit ?? (int) config('in_app_notifications.poll_limit', 15);

        return DB::table($this->table())
            ->where('user_id', $userId)
            ->where('status', $this->unreadStatus())
            ->where('is_delete', 0)
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'notification_type',
                'title',
                'message',
                'action_url',
                'action_mode',
                'entity_type',
                'entity_id',
                'created_on',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'type' => (string) $row->notification_type,
                'title' => (string) $row->title,
                'message' => (string) ($row->message ?? ''),
                'action_url' => (string) ($row->action_url ?? ''),
                'action_mode' => (string) ($row->action_mode ?? 'redirect'),
                'entity_type' => (string) ($row->entity_type ?? ''),
                'entity_id' => (int) ($row->entity_id ?? 0),
                'created_on' => (string) ($row->created_on ?? ''),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  bool  $incremental  When true, return new_items with id greater than sinceId (sinceId may be 0).
     * @return array{unread_count:int,latest_id:int,notifications:array<int,array<string,mixed>>,new_items:array<int,array<string,mixed>>}
     */
    public function poll(int $userId, ?int $sinceId = null, bool $incremental = false): array
    {
        $notifications = $this->recentUnread($userId);
        $latestId = $notifications !== [] ? (int) $notifications[0]['id'] : 0;

        $newItems = [];
        if ($incremental) {
            $cursor = $sinceId ?? 0;
            $newItems = array_values(array_filter(
                $notifications,
                static fn ($item) => (int) $item['id'] > $cursor
            ));
        }

        return [
            'unread_count' => $this->unreadCount($userId),
            'latest_id' => $latestId,
            'notifications' => $notifications,
            'new_items' => $newItems,
        ];
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        if (!Schema::hasTable($this->table()) || $notificationId <= 0 || $userId <= 0) {
            return false;
        }

        $now = function_exists('current_datetime') ? current_datetime() : now()->format('Y-m-d H:i:s');

        return DB::table($this->table())
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->where('is_delete', 0)
            ->update([
                'status' => $this->readStatus(),
                'updated_by' => $userId,
                'updated_on' => $now,
            ]) > 0;
    }

    /**
     * Filter user ids to active users in the configured users table.
     *
     * @param  int[]  $userIds
     * @return int[]
     */
    public function filterActiveUserIds(array $userIds, ?int $excludeUserId = null): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($excludeUserId) {
            $userIds = array_values(array_filter($userIds, static fn ($id) => $id > 0 && $id !== $excludeUserId));
        }

        if ($userIds === []) {
            return [];
        }

        $usersTable = (string) config('in_app_notifications.users_table', 'tbl_user');
        if (!Schema::hasTable($usersTable)) {
            return $userIds;
        }

        $activeStatus = config('in_app_notifications.active_user_status', 1);

        $query = DB::table($usersTable)->whereIn('id', $userIds);
        if (Schema::hasColumn($usersTable, 'status')) {
            $query->where('status', $activeStatus);
        }
        if (Schema::hasColumn($usersTable, 'is_delete')) {
            $query->where('is_delete', 0);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
