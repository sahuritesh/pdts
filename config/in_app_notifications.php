<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */
    'table' => env('IN_APP_NOTIFICATIONS_TABLE', 'tbl_user_in_app_notifications'),
    'users_table' => env('IN_APP_NOTIFICATIONS_USERS_TABLE', 'tbl_user'),

    'status_unread' => 0,
    'status_read' => 1,

    /*
    |--------------------------------------------------------------------------
    | Polling (frontend)
    |--------------------------------------------------------------------------
    */
    'poll_interval_ms' => (int) env('IN_APP_NOTIFICATIONS_POLL_MS', 8000),
    'poll_limit' => (int) env('IN_APP_NOTIFICATIONS_POLL_LIMIT', 15),

    /*
    |--------------------------------------------------------------------------
    | HTTP routes (relative to project root URL)
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'poll' => 'in-app-notifications/poll',
        'mark_read' => 'in-app-notifications/mark-read',
        'list' => 'in-app-notifications/list',
        'list_data' => 'get_in_app_notification_list',
    ],

    'middleware' => ['web', 'Admin', 'SanitizePostData'],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    */
    'asset_version' => env('IN_APP_NOTIFICATIONS_ASSET_VERSION', '1.3'),
    'js_global_config_key' => 'inAppNotificationConfig',

    /*
    |--------------------------------------------------------------------------
    | User validation when sending notifications
    |--------------------------------------------------------------------------
    */
    'active_user_status' => defined('ACTIVE') ? ACTIVE : 1,
];
