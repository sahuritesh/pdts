<?php

namespace App\Http\Traits;

use App\Models\Common_model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\FirebaseNotificationService;

trait LoginTrait
{
    /**
     * Process login authentication and redirect
     * 
     * @param string $emailFieldName Field name for email (e.g., 'username' for admin, 'email_id' for frontend)
     * @param array $postData POST data from request
     * @param string $errorRedirectUrl URL to redirect on error
     * @param bool $requireCaptcha Whether captcha validation is required
     * @param \Illuminate\Http\Request|null $request Optional request object for accessing device token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processLogin($emailFieldName, $postData, $errorRedirectUrl, $requireCaptcha = false, $request = null)
    {
        try {
            // Validate login data
            $errMessage = $this->validateLoginData($postData, $emailFieldName, $requireCaptcha);

            if (!empty($errMessage)) {
                return redirect($errorRedirectUrl)->with('error', $errMessage);
            }

            $username = trim($postData[$emailFieldName]);
            $password = trim($postData['password']);

            // Attempt authentication
            if (Auth::attempt(['email_id' => $username, 'password' => $password])) {
                $user = Auth::user();

                // Check if user is active
                if (empty($user->status) || $user->status != ACTIVE) {
                    Session::flush();
                    Auth::logout();
                    return redirect($errorRedirectUrl)->with('error', 'Your account is inactive. Please contact support.');
                }

                // Update last login time in tbl_user
                $this->updateLastLogin($user->id);

                // Set session data (uses user->id from tbl_user)
                $this->setUserSession($user);
                
                // Update device token for web users if available
                $this->updateWebDeviceToken($user->id, $request, $postData);

                // Check if user has multiple roles/registrations
                // All checks are based on user->id from tbl_user
                $linkedRegistrations = session('linked_registrations', []);
                if (hasMultipleRoles($user->user_type, $linkedRegistrations)) {
                    // Redirect to role selection page
                    return redirect(getProjectUrl('select-role'))->with('success', 'Please select a role to continue.');
                }

                // Single role - redirect directly
                $redirectPath = getRedirectPathForUserType($user->user_type);
                return redirect($redirectPath)->with('success', 'Successfully logged in.');
            } else {
                return redirect($errorRedirectUrl)->with('error', 'Email Id / Password is wrong');
            }
        } catch (\Exception $e) {
            Log::error('Login Submit Error: ' . $e->getMessage());
            Log::error($e);
            return redirect($errorRedirectUrl)->with('error', 'An error occurred during login. Please try again.');
        }
    }

    /**
     * Validate login data
     * 
     * @param array $postData POST data
     * @param string $emailFieldName Field name for email
     * @param bool $requireCaptcha Whether captcha is required
     * @return string Error message (empty if valid)
     */
    protected function validateLoginData($postData, $emailFieldName = 'email_id', $requireCaptcha = false)
    {
        $errMessage = '';
        
        // Required fields
        $mandatoryFields = [$emailFieldName, 'password'];
        
        if ($requireCaptcha) {
            $mandatoryFields[] = 'captcha';
        }

        foreach ($mandatoryFields as $fieldname) {
            $fieldValue = trim($postData[$fieldname] ?? '');
            if (empty($fieldValue)) {
                $fieldName = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                $errMessage .= "Please Enter $fieldName. ";
            }
        }

        // Validate captcha if required
        if ($requireCaptcha && !empty($postData['captcha'])) {
            $captcha = session('captcha', '');
            if ($captcha != $postData['captcha']) {
                $errMessage .= "Invalid Captcha, Please try again";
            }
        }

        return trim($errMessage);
    }

    /**
     * Update last login time
     * 
     * @param int $userId User ID
     * @return void
     */
    protected function updateLastLogin($userId)
    {
        try {
            $data = [
                'last_logged_on' => current_datetime()
            ];

            Common_model::updateDataFromTable('tbl_user', $data, 'id', $userId);
        } catch (\Exception $e) {
            Log::error('Update Last Login Error: ' . $e->getMessage());
            // Don't fail login if this fails
        }
    }

    /**
     * Set user session data
     * 
     * @param object $user User object
     * @return void
     */
    protected function setUserSession($user)
    {
        try {
            // Generate and store API token
            $apiToken = Str::random(60);
            Session::put('session_id', $apiToken);

            // Get user permissions
            $permissiontypes = Common_model::getDataFromTable(
                'tbl_roles',
                ['permission_types'],
                ['id' => $user->user_type],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (!empty($permissiontypes) && isset($permissiontypes[0]['permission_types'])) {
                $permissions = array_values(array_filter(array_map('trim', explode(',', $permissiontypes[0]['permission_types']))));
                Session::put('permissiontypes', $permissions);
            }

            Session::put('effective_role_id', $user->user_type);

            // Always load linked registrations for ALL users based on user_id from tbl_user
            // This allows users with mixed registrations to access both frontend and backend
            // user_id from tbl_user is the single source of truth for all linked registrations
            $linkedRegistrations = $this->getLinkedRegistrations($user->id);
            Session::put('linked_registrations', $linkedRegistrations);
        } catch (\Exception $e) {
            Log::error('Set User Session Error: ' . $e->getMessage());
            Log::error($e);
            // Don't fail login if this fails
        }
    }

    /**
     * Get redirect path based on user type
     * Uses centralized configuration from constants
     * 
     * @param int $userType User type constant
     * @return string Redirect path
     */
    protected function getRedirectPathForUserType($userType)
    {
        return getRedirectPathForUserType($userType);
    }

    /**
     * Check if user is already logged in and redirect accordingly
     * 
     * @param object|null $user Current authenticated user
     * @return \Illuminate\Http\RedirectResponse|null Returns redirect if logged in, null otherwise
     */
    protected function redirectIfAlreadyLoggedIn($user)
    {
        if (!empty($user)) {
            // Avoid redirect loop when user lacks dashboard access (dashboard sends them to logout/admin).
            if (permissionexists('dashboard_view') != '1') {
                return null;
            }

            $redirectPath = $this->getRedirectPathForUserType($user->user_type);
            if ($redirectPath) {
                return redirect($redirectPath)->with('success', 'Already logged in');
            }
        }
        return null;
    }

    /**
     * Get linked registrations for user
     * Shared method used by both web and API login
     * 
     * @param int $userId User ID
     * @return array Linked registrations array
     */
    protected function getLinkedRegistrations($userId)
    {
        $linkedRegistrations = [
            'membership' => [],
            'conference' => [],
            'speaker' => []
        ];

        try {
            // Membership / conference / speaker modules are optional (not present in minimal DBs).
            if (Schema::hasTable('tbl_membership_registrations')) {
                $membershipRegistrations = DB::table('tbl_membership_registrations as mr')
                    ->leftJoin('tbl_countries as tc', 'tc.id', '=', 'mr.country')
                    ->leftJoin('tbl_status as ts', 'ts.id', '=', 'mr.status')
                    ->leftJoin('tbl_payment_transactions as pt', function ($join) {
                        $join->on('pt.reference_id', '=', 'mr.id')
                            ->where('pt.registration_type', '=', 'membership');
                    })
                    ->select(
                        'mr.id as registration_id',
                        'mr.serial_number',
                        'mr.first_name',
                        'mr.last_name',
                        'mr.email_id',
                        'mr.phone_number',
                        'mr.city',
                        'tc.country_name',
                        'mr.sub_total',
                        'mr.grand_total',
                        'pt.currency',
                        'mr.source_type',
                        'ts.status_name',
                        'mr.status',
                        'mr.created_on'
                    )
                    ->where('mr.user_id', $userId)
                    ->orderBy('mr.id', 'DESC')
                    ->get();

                if ($membershipRegistrations->isNotEmpty()) {
                    foreach ($membershipRegistrations as $registration) {
                        $linkedRegistrations['membership'][] = [
                            'registration_id' => $registration->registration_id,
                            'serial_number' => $registration->serial_number,
                            'full_name' => trim(($registration->first_name ?? '') . ' ' . ($registration->last_name ?? '')),
                            'email' => $registration->email_id,
                            'phone' => $registration->phone_number,
                            'city' => $registration->city,
                            'country' => $registration->country_name,
                            'sub_total' => $registration->sub_total,
                            'grand_total' => $registration->grand_total,
                            'currency' => $registration->currency ?? 'INR',
                            'source_type' => $registration->source_type,
                            'status' => $registration->status,
                            'status_name' => $registration->status_name,
                            'created_on' => $registration->created_on,
                        ];
                    }
                }
            }

            // Fetch conference registrations linked to this user
            if (!Schema::hasTable('tbl_conference_registrations')) {
                $conferenceRegistrations = collect();
            } else {
                $conferenceRegistrations = DB::table('tbl_conference_registrations as cr')
                ->leftJoin('tbl_events as te', 'te.id', '=', 'cr.event_id')
                ->leftJoin('tbl_modules as tm', 'tm.id', '=', 'cr.module_id')
                ->leftJoin('tbl_categories as tc', 'tc.id', '=', 'cr.category_id')
                ->leftJoin('tbl_tickets as tk', 'tk.id', '=', 'cr.ticket_id')
                ->leftJoin('tbl_countries as tco', 'tco.id', '=', 'cr.country')
                ->leftJoin('tbl_status as ts', 'ts.id', '=', 'cr.status')
                ->select(
                    'cr.id as registration_id',
                    'cr.serial_number',
                    'cr.full_name',
                    'cr.email',
                    'cr.mobile',
                    'cr.institute',
                    'cr.specialization',
                    'cr.city',
                    'tco.country_name',
                    'cr.event_id',
                    'te.event_name',
                    'cr.module_id',
                    'tm.module_name',
                    'tc.name as category_name',
                    'tc.is_residential',
                    'tk.name as ticket_name',
                    'cr.efi_type',
                    'cr.category_type',
                    'cr.sub_total',
                    'cr.grand_total',
                    'cr.currency',
                    'cr.source_type',
                    'ts.status_name',
                    'cr.status',
                    'cr.created_on'
                )
                ->where('cr.user_id', $userId)
                ->orderBy('cr.id', 'DESC')
                ->get();
            }

            if ($conferenceRegistrations->isNotEmpty()) {
                foreach ($conferenceRegistrations as $registration) {
                    $linkedRegistrations['conference'][] = [
                        'registration_id' => $registration->registration_id,
                        'serial_number' => $registration->serial_number,
                        'full_name' => $registration->full_name,
                        'email' => $registration->email,
                        'mobile' => $registration->mobile,
                        'institute' => $registration->institute,
                        'specialization' => $registration->specialization,
                        'city' => $registration->city,
                        'country' => $registration->country_name,
                        'event_id' => $registration->event_id ?? null,
                        'event_name' => $registration->event_name,
                        'module_id' => $registration->module_id ?? null,
                        'module_name' => $registration->module_name,
                        'category_name' => $registration->category_name,
                        'is_residential' => (bool)$registration->is_residential,
                        'ticket_name' => $registration->ticket_name,
                        'efi_type' => $registration->efi_type,
                        'member_type' => $registration->efi_type == 'member' ? 'Member' : 'Non Member',
                        'category_type' => $registration->category_type,
                        'sub_total' => $registration->sub_total,
                        'grand_total' => $registration->grand_total,
                        'currency' => $registration->currency ?? 'INR',
                        'source_type' => $registration->source_type,
                        'status' => $registration->status,
                        'status_name' => $registration->status_name,
                        'created_on' => $registration->created_on,
                    ];
                }
            }

            // Fetch speaker registrations linked to this user
            if (!Schema::hasTable('tbl_speakers')) {
                $speakerRegistrations = collect();
            } else {
                $speakerRegistrations = DB::table('tbl_speakers as sp')
                    ->leftJoin('tbl_qualifications as tq', 'tq.id', '=', 'sp.qualification')
                    ->leftJoin('tbl_specializations as ts', 'ts.id', '=', 'sp.specialization')
                    ->leftJoin('tbl_status as tst', 'tst.id', '=', 'sp.status')
                    ->select(
                        'sp.id as speaker_id',
                        'sp.serial_number',
                        'sp.first_name',
                        'sp.last_name',
                        'sp.email',
                        'sp.phone',
                        'sp.qualification',
                        'tq.qualification_name',
                        'sp.specialization',
                        'ts.specialization_name',
                        'sp.bio',
                        'sp.profile_image',
                        'tst.status_name',
                        'sp.status',
                        'sp.created_at',
                        'sp.updated_at'
                    )
                    ->where('sp.user_id', $userId)
                    ->orderBy('sp.id', 'DESC')
                    ->get();
            }

            if ($speakerRegistrations->isNotEmpty()) {
                foreach ($speakerRegistrations as $speaker) {
                    $linkedRegistrations['speaker'][] = [
                        'speaker_id' => $speaker->speaker_id,
                        'serial_number' => $speaker->serial_number,
                        'first_name' => $speaker->first_name,
                        'last_name' => $speaker->last_name,
                        'full_name' => trim(($speaker->first_name ?? '') . ' ' . ($speaker->last_name ?? '')),
                        'email' => $speaker->email,
                        'phone' => $speaker->phone,
                        'qualification' => $speaker->qualification_name,
                        'qualification_id' => $speaker->qualification ?? null,
                        'specialization' => $speaker->specialization_name,
                        'specialization_id' => $speaker->specialization ?? null,
                        'bio' => $speaker->bio,
                        'profile_image' => $speaker->profile_image ? url($speaker->profile_image) : null,
                        'status' => $speaker->status,
                        'status_name' => $speaker->status_name,
                        'created_at' => $speaker->created_at,
                        'updated_at' => $speaker->updated_at,
                    ];
                }
            }

        } catch (\Exception $e) {
            Log::error('Get Linked Registrations Error: ' . $e->getMessage());
            Log::error($e);
            // Return empty arrays on error - don't fail the login
        }

        return $linkedRegistrations;
    }

    /**
     * Update web device token after login
     * Captures device token from POST request if available, otherwise relies on frontend JavaScript
     * 
     * @param int $userId User ID
     * @param \Illuminate\Http\Request|null $request Request object (optional)
     * @param array $postData POST data array (optional, fallback if request not provided)
     * @return void
     */
    protected function updateWebDeviceToken($userId, $request = null, $postData = [])
    {
        try {
            $deviceToken = null;
            $deviceId = null;
            $platform = 'web';
            
            // Try to get device token from request object first
            if ($request instanceof \Illuminate\Http\Request) {
                $deviceToken = $request->input('device_token') ? trim($request->input('device_token')) : null;
                $deviceId = $request->input('device_id') ? trim($request->input('device_id')) : null;
                $platform = $request->input('platform', 'web');
            } 
            // Fallback to POST data array
            elseif (!empty($postData)) {
                $deviceToken = !empty($postData['device_token']) ? trim($postData['device_token']) : null;
                $deviceId = !empty($postData['device_id']) ? trim($postData['device_id']) : null;
                $platform = !empty($postData['platform']) ? trim($postData['platform']) : 'web';
            }
            
            // If device token is provided in the request, register it directly
            if (!empty($deviceToken)) {
                try {
                    $firebaseService = app(\App\Services\FirebaseNotificationService::class);
                    
                    $result = $firebaseService->registerDeviceToken(
                        $userId,
                        $deviceToken,
                        $deviceId,
                        $platform,
                        null // app_version - not typically available in web login
                    );
                    
                    // Token registration result is not logged (silent success/failure)
                } catch (\Exception $e) {
                    Log::error('Error registering web device token during login: ' . $e->getMessage(), [
                        'user_id' => $userId,
                        'device_id' => $deviceId,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail login if device token registration fails
                }
            }
        } catch (\Exception $e) {
            Log::error('Update Web Device Token Error: ' . $e->getMessage());
            // Don't fail login if this fails - frontend will handle it
        }
    }
}

