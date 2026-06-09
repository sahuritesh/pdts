<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Normalize REQUEST_URI to project-relative routes when app is hosted in a subfolder.
 * Also strips accidental /public segment so both /app/... and /app/public/... work.
 *
 * Strip the path portion of APP_URL from REQUEST_URI before routing runs.
 */
class NormalizeUrlFromAppBase
{
    public function handle(Request $request, Closure $next)
    {
        $appUrl = config('app.url');
        if (empty($appUrl)) {
            return $next($request);
        }

        $basePath = parse_url($appUrl, PHP_URL_PATH);
        if (empty($basePath) || $basePath === '/') {
            return $next($request);
        }

        $requestUri = $request->server->get('REQUEST_URI', '');
        $pathOnly = strtok($requestUri, '?');
        $queryString = '';
        if (strpos($requestUri, '?') !== false) {
            $queryString = substr($requestUri, strpos($requestUri, '?'));
        }

        if (!Str::startsWith($pathOnly, $basePath)) {
            return $next($request);
        }

        $trimmed = substr($pathOnly, strlen($basePath));
        if ($trimmed === '' || $trimmed === false) {
            $trimmed = '/';
        } elseif ($trimmed[0] !== '/') {
            $trimmed = '/' . $trimmed;
        }

        if (Str::startsWith($trimmed, '/public/')) {
            $trimmed = substr($trimmed, 7);
            if ($trimmed === '' || $trimmed === false) {
                $trimmed = '/';
            }
        } elseif ($trimmed === '/public') {
            $trimmed = '/';
        }

        $request->server->set('REQUEST_URI', $trimmed . $queryString);

        return $next($request);
    }
}
