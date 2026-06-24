# In-App Notifications Module

Portable Laravel module for **per-user in-app notifications** with bell UI, unread badge, dropdown list, background polling, and toastr popups.

**Full PDTS documentation:** [`documentation/IN_APP_NOTIFICATIONS.md`](../../../documentation/IN_APP_NOTIFICATIONS.md) — architecture, API, delay integration, troubleshooting, and every configuration point.

## Quick install (new project)

1. Copy `app/Modules/InAppNotifications/` → target project
2. Copy `config/in_app_notifications.php`
3. Copy migration → run `php artisan migrate`
4. Copy `assets/js/in-app-notifications.js`
5. Register `InAppNotificationsServiceProvider` in `config/app.php`
6. Autoload `helpers.php` in `composer.json`; `composer dump-autoload`
7. Layout: `@include('in-app-notifications::bell')` and `@include('in-app-notifications::scripts')` after `ajaxPromise.js`
8. Set middleware to include **`web`** for session/auth

## Send a notification

```php
in_app_notify($userId, 'delay_logged', 'Title', 'Message...', [
    'action_url' => 'projects/wizard/' . Crypt::encrypt($projectId),
    'action_mode' => 'redirect',
    'entity_type' => 'delay_register',
    'entity_id' => $delayId,
    'triggered_by' => auth()->id(),
]);
```

## Domain notifiers

Keep business rules **outside** this module. PDTS example: `App\Services\DelayNotificationService`.
