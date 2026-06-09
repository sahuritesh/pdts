<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Closure;

class FrontendMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $error_msg = 'Please login to access this page.';
        
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if user has frontend access via:
            // 1. Frontend user type, OR
            // 2. Selected role is 'frontend', OR
            // 3. Has frontend registrations and no role selected yet
            $selectedRole = session('selected_role');
            $linkedRegistrations = session('linked_registrations', []);
            $hasFrontendRegistrations = !empty($linkedRegistrations['membership']) || !empty($linkedRegistrations['conference']);
            
            $hasFrontendAccess = isFrontendUserType($user->user_type) 
                || $selectedRole === 'frontend'
                || ($hasFrontendRegistrations && empty($selectedRole));
            
            if (!$hasFrontendAccess) {
                // User doesn't have frontend access
                $redirectPath = getRedirectPathForUserType($user->user_type);
                return Redirect::to($redirectPath)->with('error', 'Access denied. This page is for members only.');
            }
            
            // Check if user is active
            if (empty($user->status) || $user->status != ACTIVE) {
                Auth::logout();
                return Redirect::to(url('/login'))->with('error', 'Your account is inactive. Please contact support.');
            }
            
            $response = $next($request);
            $response->headers->set('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
            return $response;
        }
        
        return Redirect::to(url('/login'))->with('error', $error_msg);
    }
}

