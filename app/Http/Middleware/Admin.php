<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Closure;
// use Auth;

class Admin
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
        $error_msg = 'Invalid Authentication';
        
        if (Auth::check()) {
            $user = Auth::user();
            
            $selectedRoleId = session('selected_role_id'); // Get selected role ID
            $linkedRegistrations = session('linked_registrations', []);
            
            // Get all available role IDs for this user
            $availableRoleIds = getAvailableRoleIdsForUser($user->user_type, $linkedRegistrations);
            
            // If a role is selected, validate it's available and is a backend role
            if (!empty($selectedRoleId)) {
                // Check if selected role is in available roles
                if (!in_array($selectedRoleId, $availableRoleIds)) {
                    // Invalid role selection - clear session and deny
                    session()->forget('selected_role_id');
                    session()->forget('selected_role');
                    session()->forget('selected_role_redirect');
                    $redirectPath = getRedirectPathForUserType($user->user_type);
                    return Redirect::to($redirectPath)->with('error', 'Access denied. Invalid role selection.');
                }
                
                // Check if selected role is a backend role
                if (!isBackendUserType($selectedRoleId)) {
                    // Selected role is frontend - deny backend access
                    session()->forget('selected_role_id');
                    session()->forget('selected_role');
                    session()->forget('selected_role_redirect');
                    $redirectPath = getRedirectPathForUserType($user->user_type);
                    return Redirect::to($redirectPath)->with('error', 'Access denied. This area is for backend users only.');
                }
                
                // User has valid backend role selected - allow access
            } else {
                // No role selected - check if user has any backend role available
                $hasBackendRole = false;
                foreach ($availableRoleIds as $roleId) {
                    if (isBackendUserType($roleId)) {
                        $hasBackendRole = true;
                        break;
                    }
                }
                
                if (!$hasBackendRole) {
                    // User has no backend roles available - deny access
                    $redirectPath = getRedirectPathForUserType($user->user_type);
                    return Redirect::to($redirectPath)->with('error', 'Access denied. Admin privileges required.');
                }
            }
            
            // Check if user account is active
            if (empty($user->status) || $user->status != ACTIVE) {
                Auth::logout();
                $url = getbaseUrl();
                return Redirect::to($url)->with('error', 'Your account is inactive. Please contact support.');
            }
            
            // User is authenticated and is an admin
            $response = $next($request);
            $response->headers->set('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
            return $response;
        }
        
        // User is not authenticated (not logged in, session expired, or cookie host mismatch)
        $url = getbaseUrl();
        return Redirect::to($url)->with('error', $error_msg);
    }
}