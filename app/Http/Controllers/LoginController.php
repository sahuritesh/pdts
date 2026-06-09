<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use App\Http\Traits\WebResponseTrait;
use App\Http\Traits\LoginTrait;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use WebResponseTrait, LoginTrait;

    /**
     * Display admin login form
     */
    public function adminLogin(Request $request)
    {
        try {
            $user = auth()->user();
            $pageTitle = "CMS Login";
            $data['captcha'] = $this->generateCaptcha();

            // Check if already logged in and redirect accordingly
            $redirect = $this->redirectIfAlreadyLoggedIn($user);
            if ($redirect) {
                return $redirect;
            }

            return view('layouts.login', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('Admin Login Page Error: ' . $e->getMessage());
            Log::error($e);
            $pageTitle = "CMS Login";
            $data['captcha'] = $this->generateCaptcha();
            return view('layouts.login', compact('pageTitle', 'data'));
        }
    }

    /**
     * Submit login form
     */
    public function Loginsubmit(Request $request)
    {
        $postData = $request->post();
        $url = getbaseUrl();
        
        // Use shared login processing (admin uses 'username' field and requires captcha)
        // Pass request object so device token can be captured if provided
        $requireCaptcha = !app()->environment('local');
        return $this->processLogin('username', $postData, $url, $requireCaptcha, $request);
    }

    /**
     * Display role selection page
     * Can be accessed during login or to switch roles after login
     */
    public function selectRole()
    {
        try {
            $user = auth()->user();
            
            if (empty($user)) {
                return redirect(getProjectUrl('admin'))->with('error', 'Please login first.');
            }

            $linkedRegistrations = session('linked_registrations', []);
            
            // If linked_registrations is empty, reload from database
            if (empty($linkedRegistrations) || (empty($linkedRegistrations['membership']) && empty($linkedRegistrations['conference']) && empty($linkedRegistrations['speaker']))) {
                if (method_exists($this, 'getLinkedRegistrations')) {
                    $linkedRegistrations = $this->getLinkedRegistrations($user->id);
                    Session::put('linked_registrations', $linkedRegistrations);
                }
            }
            
            $availableRoles = getAvailableRolesForUser($user->user_type, $linkedRegistrations);

            // If only one role, redirect directly (unless user explicitly wants to switch)
            if (count($availableRoles) == 1) {
                // If user came from a switch request, still show the page
                // Otherwise redirect to their only available role
                $redirectPath = $availableRoles[0]['redirect'];
                return redirect($redirectPath)->with('info', 'You only have access to one role.');
            }

            $pageTitle = "Select Role";
            return view('auth.select-role', compact('pageTitle', 'availableRoles'));
        } catch (\Exception $e) {
            Log::error('Select Role Page Error: ' . $e->getMessage());
            Log::error($e);
            return redirect(getProjectUrl('admin'))->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Handle role selection and redirect
     * Now accepts role_id instead of role_type for simpler, more flexible approach
     */
    public function switchRole(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (empty($user)) {
                return redirect(getProjectUrl('admin'))->with('error', 'Please login first.');
            }

            $selectedRoleId = (int)$request->input('role_id'); // Actual role ID from tbl_roles - cast to int for type safety
            
            // DEBUG: Log role selection
            Log::info('=== SWITCH ROLE DEBUG START ===');
            Log::info('Selected Role ID from request: ' . $selectedRoleId . ' (type: ' . gettype($selectedRoleId) . ')');
            Log::info('User ID: ' . $user->id);
            Log::info('User base user_type: ' . $user->user_type);

            if (empty($selectedRoleId)) {
                Log::error('No role_id provided in request');
                return redirect(getProjectUrl('select-role'))->with('error', 'Invalid role selection.');
            }

            // Validate that user has access to the selected role
            $linkedRegistrations = session('linked_registrations', []);
            $availableRoleIds = getAvailableRoleIdsForUser($user->user_type, $linkedRegistrations);
            
            Log::info('Available Role IDs: ' . json_encode($availableRoleIds));
            Log::info('Linked Registrations: ' . json_encode($linkedRegistrations));
            
            // Check if the selected role ID is actually available for this user
            if (!in_array($selectedRoleId, $availableRoleIds)) {
                Log::error('Selected role ID not in available roles', [
                    'selected' => $selectedRoleId,
                    'available' => $availableRoleIds
                ]);
                return redirect(getProjectUrl('select-role'))->with('error', 'You do not have access to the selected role.');
            }

            // Get role details to determine redirect path and validate access
            $role = DB::table('tbl_roles')
                ->select('id', 'role_name', 'role_description')
                ->where('id', $selectedRoleId)
                ->where('status', ACTIVE)
                ->first();
            
            if (!$role) {
                Log::error('Role not found or inactive', ['role_id' => $selectedRoleId]);
                return redirect(getProjectUrl('select-role'))->with('error', 'Selected role not found or inactive.');
            }
            
            Log::info('Role found: ' . $role->role_name . ' (ID: ' . $role->id . ')');

            // Get available roles to find the correct redirect path for this role
            $availableRoles = getAvailableRolesForUser($user->user_type, $linkedRegistrations);
            Log::info('Available Roles Array: ' . json_encode($availableRoles, JSON_PRETTY_PRINT));
            
            $redirectPath = null;
            
            // Find the redirect path for the selected role from available roles
            foreach ($availableRoles as $availableRole) {
                Log::info('Checking available role: ' . json_encode($availableRole));
                if ($availableRole['role_id'] == $selectedRoleId) {
                    $redirectPath = $availableRole['redirect'];
                    Log::info('Found redirect path from availableRoles: ' . $redirectPath);
                    break;
                }
            }
            
            // If redirect path not found, default to project dashboard URL.
            if (empty($redirectPath)) {
                Log::warning('Redirect path not found in availableRoles, defaulting to dashboard');
                $redirectPath = getProjectUrl('dashboard');
                Log::info('Set redirect to dashboard');
            }

            // Ensure redirect stays under project dashboard/admin URLs.
            if (strpos($redirectPath, 'dashboard') === false && strpos($redirectPath, 'admin') === false) {
                Log::warning('Redirect is not dashboard/admin, forcing dashboard');
                $redirectPath = getProjectUrl('dashboard');
            }
            
            Log::info('Final redirect path: ' . $redirectPath);
            Log::info('=== SWITCH ROLE DEBUG END ===');

            // Reload permissions for the selected role
            $this->reloadPermissionsForRole($selectedRoleId);

            // Store selected role information in session
            Session::put('selected_role_id', $selectedRoleId); // Store actual role ID
            Session::put('selected_role', $isFrontendRole ? 'frontend' : 'backend'); // Keep for backward compatibility
            Session::put('selected_role_redirect', $redirectPath);
            Session::put('effective_role_id', $selectedRoleId); // Store effective role for display

            return redirect($redirectPath)->with('success', 'Role selected successfully.');
        } catch (\Exception $e) {
            Log::error('Switch Role Error: ' . $e->getMessage());
            Log::error($e);
            return redirect(getProjectUrl('select-role'))->with('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Reload permissions for a specific role
     * Used when switching roles to update permissions in session
     * 
     * @param int $roleId Role ID to load permissions from
     * @return void
     */
    protected function reloadPermissionsForRole($roleId)
    {
        try {
            // Get permissions for the specified role
            $permissiontypes = Common_model::getDataFromTable(
                'tbl_roles',
                ['permission_types'],
                ['id' => $roleId],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (!empty($permissiontypes) && isset($permissiontypes[0]['permission_types'])) {
                $permissions = explode(',', $permissiontypes[0]['permission_types']);
                Session::put('permissiontypes', $permissions);
                Log::info('Permissions reloaded for role', ['role_id' => $roleId, 'permissions_count' => count($permissions)]);
            } else {
                Log::warning('No permissions found for role', ['role_id' => $roleId]);
                // Clear permissions if role has none
                Session::put('permissiontypes', []);
            }
        } catch (\Exception $e) {
            Log::error('Reload Permissions Error: ' . $e->getMessage(), ['role_id' => $roleId]);
            // Don't fail role switch if permission reload fails
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            Session::forget('password_hash');
            Session::flush();
            Auth::logout();

            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            $url = getbaseUrl();
            return Redirect::to($url)->with('success', 'Successfully logged out!!');
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            Log::error($e);
            $url = getbaseUrl();
            return Redirect::to($url)->with('error', 'An error occurred during logout.');
        }
    }

    /**
     * Generate captcha image
     */
    public function generateCaptcha()
    {
        try {
            $captchaText = mt_rand(100000, 999999);
            session(['captcha' => $captchaText]);

            $img = Image::canvas(150, 50, '#fff');
            $img->text($captchaText, 75, 25, function ($font) {
                $font->file(app_path('../assets/fonts/Roboto-Italic.ttf'));
                $font->size(24);
                $font->color('#333');
                $font->align('center');
                $font->valign('middle');
            });

            $filename = 'captcha_' . time() . '.png';
            $captchaDir = base_path('captcha');
            if (!is_dir($captchaDir)) {
                mkdir($captchaDir, 0777, true);
            }
            $path = $captchaDir . DIRECTORY_SEPARATOR . $filename;
            $img->save($path);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Generate Captcha Error: ' . $e->getMessage());
            Log::error($e);
            return '';
        }
    }

    /**
     * Refresh captcha
     */
    public function refreshCaptcha(Request $request)
    {
        try {
            Session::forget('captcha');
            $res['file'] = $this->generateCaptcha();
            $res['error'] = 0;
            return response()->json($res);
        } catch (\Exception $e) {
            Log::error('Refresh Captcha Error: ' . $e->getMessage());
            Log::error($e);
            return response()->json([
                'error' => 1,
                'file' => '',
                'msg' => 'Failed to refresh captcha'
            ]);
        }
    }

}
