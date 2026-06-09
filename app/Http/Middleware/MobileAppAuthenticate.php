<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MobileAppAuthenticate
{
    /**
     * Handle an incoming request.
     * Validates static bearer token for mobile app API calls
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $api_token = $request->bearerToken();
        // Get token from constant (defined in config/constants.php)
        $static_token = defined('MOBILE_APP_API_TOKEN') ? MOBILE_APP_API_TOKEN : '';
        
        // Also try to get from env directly as fallback
        if (empty($static_token)) {
            $static_token = env('MOBILE_APP_API_TOKEN', '');
        }
        
        // Trim both tokens to handle whitespace issues
        $api_token = trim($api_token ?? '');
        $static_token = trim($static_token ?? '');
        
        // Check if static token is configured
        if (empty($static_token)) {
            \Log::error('MobileAppAuthenticate: MOBILE_APP_API_TOKEN is not configured or is empty', [
                'constant_defined' => defined('MOBILE_APP_API_TOKEN'),
                'constant_value' => defined('MOBILE_APP_API_TOKEN') ? (strlen(MOBILE_APP_API_TOKEN) > 0 ? 'SET (length: ' . strlen(MOBILE_APP_API_TOKEN) . ')' : 'EMPTY') : 'NOT_DEFINED',
                'env_value' => env('MOBILE_APP_API_TOKEN') ? 'SET (length: ' . strlen(env('MOBILE_APP_API_TOKEN')) . ')' : 'NOT_SET'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'API authentication is not configured. Please contact administrator.',
                'status' => 500
            ], 500);
        }
        
        // Check if token is provided
        if (empty($api_token)) {
            \Log::warning('MobileAppAuthenticate: No bearer token provided', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Authorization token is required. Please provide a valid bearer token.',
                'status' => 401
            ], 401);
        }
        
        // Validate token (case-sensitive comparison)
        if ($api_token !== $static_token) {
            \Log::warning('MobileAppAuthenticate: Token mismatch', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'provided_token_length' => strlen($api_token),
                'expected_token_length' => strlen($static_token),
                'tokens_match' => $api_token === $static_token,
                'provided_token_preview' => substr($api_token, 0, 10) . '...',
                'expected_token_preview' => substr($static_token, 0, 10) . '...'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid authorization token. Access denied.',
                'status' => 401
            ], 401);
        }
        
        return $next($request);
    }
}

