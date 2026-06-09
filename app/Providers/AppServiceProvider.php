<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Load constants file
        if (file_exists(config_path('constants.php'))) {
            require_once config_path('constants.php');
        }

        $ritesh = database_path('migrations/ritesh');
        if (is_dir($ritesh)) {
            $this->loadMigrationsFrom($ritesh);
        }

        // Subfolder installs: force URL root so redirects stay under the app base path.
        // On local, match the browser host (localhost vs 127.0.0.1) so session cookies are not lost.
        $rootUrl = $this->resolveProjectRootUrl();
        if (!empty($rootUrl)) {
            URL::forceRootUrl($rootUrl);
        }

        $sessionPath = parse_url((string) config('app.url'), PHP_URL_PATH);
        if (!empty($sessionPath) && $sessionPath !== '/') {
            Config::set('session.path', rtrim($sessionPath, '/') ?: '/');
        }
    }

    /**
     * Project root URL for link/redirect generation.
     */
    private function resolveProjectRootUrl(): string
    {
        $configured = function_exists('getProjectRootUrl')
            ? getProjectRootUrl()
            : rtrim((string) config('app.url'), '/');

        if (!$this->app->environment('local') || $this->app->runningInConsole()) {
            return $configured;
        }

        $request = $this->app->make('request');
        $basePath = parse_url($configured, PHP_URL_PATH);
        if (empty($basePath) || $basePath === '/') {
            $basePath = '';
        } else {
            $basePath = rtrim($basePath, '/');
        }

        return rtrim($request->getSchemeAndHttpHost() . $basePath, '/');
    }
}
