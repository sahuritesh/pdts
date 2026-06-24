<?php

use App\Modules\InAppNotifications\Services\InAppNotificationService;

if (!function_exists('in_app_notify')) {
    /**
     * Send one in-app notification (reusable helper).
     *
     * @param  array<string, mixed>  $options
     */
    function in_app_notify(int $userId, string $type, string $title, string $message = '', array $options = []): ?int
    {
        return app(InAppNotificationService::class)->notifyUser($userId, $type, $title, $message, $options);
    }
}

if (!function_exists('in_app_notify_users')) {
    /**
     * @param  int[]  $userIds
     * @param  array<string, mixed>  $options
     * @return int[]
     */
    function in_app_notify_users(array $userIds, string $type, string $title, string $message = '', array $options = []): array
    {
        return app(InAppNotificationService::class)->notifyUsers($userIds, $type, $title, $message, $options);
    }
}
