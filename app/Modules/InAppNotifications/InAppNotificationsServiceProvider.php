<?php

namespace App\Modules\InAppNotifications;

use Illuminate\Support\ServiceProvider;

class InAppNotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/in_app_notifications.php',
            'in_app_notifications'
        );

        $helpers = __DIR__ . '/helpers.php';
        if (is_file($helpers)) {
            require_once $helpers;
        }

        $this->app->singleton(Services\InAppNotificationService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'in-app-notifications');

        $this->publishes([
            __DIR__ . '/config/in_app_notifications.php' => config_path('in_app_notifications.php'),
        ], 'in-app-notifications-config');

        $this->publishes([
            __DIR__ . '/assets/js/in-app-notifications.js' => public_path('assets/js/in-app-notifications.js'),
        ], 'in-app-notifications-assets');

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $middleware = array_values(array_unique(array_merge(
            ['web'],
            (array) config('in_app_notifications.middleware', ['Admin', 'SanitizePostData'])
        )));
        $poll = config('in_app_notifications.routes.poll');
        $markRead = config('in_app_notifications.routes.mark_read');
        $list = config('in_app_notifications.routes.list');
        $listData = config('in_app_notifications.routes.list_data');

        if (!$poll || !$markRead) {
            return;
        }

        \Illuminate\Support\Facades\Route::middleware($middleware)->group(function () use ($poll, $markRead, $list, $listData) {
            \Illuminate\Support\Facades\Route::get($poll, [
                Http\Controllers\InAppNotificationController::class,
                'poll',
            ]);
            \Illuminate\Support\Facades\Route::post($markRead, [
                Http\Controllers\InAppNotificationController::class,
                'markRead',
            ]);
            if ($list) {
                \Illuminate\Support\Facades\Route::get($list, [
                    Http\Controllers\InAppNotificationController::class,
                    'index',
                ]);
            }
            if ($listData) {
                \Illuminate\Support\Facades\Route::post($listData, [
                    Http\Controllers\InAppNotificationController::class,
                    'getList',
                ]);
            }
        });
    }
}
