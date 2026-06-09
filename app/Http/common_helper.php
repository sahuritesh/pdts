<?php

/**
* Write code on Method
*
* @return response()
*/
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

function current_datetime()
{
    return \Carbon\Carbon::now(config('app.timezone'))->format('Y-m-d H:i:s');
}

function current_date()
{
    return \Carbon\Carbon::now(config('app.timezone'))->format('Y-m-d');
}

function displayCustomDateTime($date)
{
    if ($date === null || $date === '') {
        return '';
    }
    if (is_string($date) && ($date === '0000-00-00 00:00:00' || $date === '0000-00-00')) {
        return '';
    }
    try {
        return \Carbon\Carbon::parse($date)->format('dS M Y h:i A');
    } catch (\Throwable $e) {
        return '';
    }
}

function displayCustomDate($date)
{
    if ($date === null || $date === '') {
        return '';
    }
    if (is_string($date) && ($date === '0000-00-00 00:00:00' || $date === '0000-00-00')) {
        return '';
    }
    try {
        return \Carbon\Carbon::parse($date)->format('dS M Y');
    } catch (\Throwable $e) {
        return '';
    }
}

function displayDateTime($date)
{
    if ($date == "0000-00-00 00:00:00" || $date == '') {
        return '<p class="text-center">-</p>';
    } else {
        $date_new = date("d-m-Y", strtotime($date));
        return $date_new;
    }
}

function displayDate($date)
{
    if ($date != '' && $date != '0000-00-00 00:00:00') {
        $date2 = date_create($date);
        $date_new = date_format($date2, "d-m-Y");
        return $date_new;
    } else {
        return '';
    }
}

function convertToDbDate($datetime){
    $datetime = str_replace('T',' ',$datetime);   
    // dd mm yy to yy mm dd converter
    if($datetime != '' && $datetime != '0000-00-00 00:00:00'){
        return date('Y-m-d H:i', strtotime($datetime));       
    }   
}

function getDateDiff($diffType, $dateTime,$dateTime2='')
{
    $currentDate = ($dateTime2=='') ?  strtotime(date('Y-m-d H:i:s')) : strtotime($dateTime2);
    $createdDate = strtotime($dateTime);
    $datediff = $currentDate - $createdDate;
    $result = '';
    if ($diffType == 'DAYS') {
        $result = round($datediff / (60 * 60 * 24)); //returns diff in days
    } else {
        $result = abs(round($datediff / (60 * 60))); //returns diff in hours
    }
    return $result;
}


function getDateDiffWithOutRound($diffType, $dateTime,$dateTime2='')
{
    $currentDate = ($dateTime2=='') ?  strtotime(date('Y-m-d H:i:s')) : strtotime($dateTime2);
    $createdDate = strtotime($dateTime);
    $datediff = $currentDate - $createdDate;
    $result = '';
    if ($diffType == 'DAYS') {
        $result = number_format(($datediff / (60 * 60 * 24)),2); //returns diff in days
    } else {
        $result = number_format(($datediff / (60 * 60)),2); //returns diff in hours
    }

    return $result;
}

if(!function_exists("getHoursDiff"))
{
    function getHoursDiff($start_data,$end_date)
    {
        $d1= new DateTime($start_data); // first date
        $d2= new DateTime($end_date); // second date
        $interval= $d1->diff($d2); // get difference between two dates
        return ($interval->days * 24) + $interval->h;
    }
}

if(!function_exists("getHoursMinsDiff"))
{
    function getHoursMinsDiff($start_data,$end_date){
        $d1= new DateTime(date("Y-m-d H:i:s",strtotime($start_data))); // first date
        $d2= new DateTime(date("Y-m-d H:i:s",strtotime($end_date))); // second date
        $interval= $d1->diff($d2); // get difference between two dates
        $total_hours = ((($interval->days * 24 * 60) + (($interval->h*60) + ($interval->i)))/60);
        return (float)number_format($total_hours,2);
    }
}

if(!function_exists("getMinsDiff"))
{
    function getMinsDiff($start_data,$end_date)
    {
        $d1= new DateTime(date("Y-m-d H:i:s",strtotime($start_data))); // first date
        $d2= new DateTime(date("Y-m-d H:i:s",strtotime($end_date))); // second date
        $interval= $d1->diff($d2); // get difference between two dates
        $days_to_minutes = ($interval->days * 24 * 60);
        $hours_to_minutes = ($interval->h * 60);
        $minute = $interval->i;
        $total_minutes = ($days_to_minutes + ($hours_to_minutes + ($minute)));
        return $total_minutes;
    }
}

function displayCustomDateInWords($date)
{
    if ($date != '' && $date != '0000-00-00 00:00:00') {
        $date2 = date_create($date);
        $date_new = date_format($date2, "F d, Y");
        return $date_new;
    } else {
        return '-';
    }
}

function displayDateWithMonthName($date)
{
    return date("F d, Y", strtotime($date));
}

function validateEmail($emailId)
{
    $pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
    $emailId = trim($emailId);
    if ($emailId != '' && preg_match($pattern, $emailId) === 1) {
        $returnval = 1;
    } else {
        $returnval = 0;
    }
    return $returnval;
}

function validateMobileNumber($mobileNumber)
{
    $mobilePattern = '/^[6|7|8|9][0-9]{8,10}$/';
    $mobile_number = trim($mobileNumber);
    if (preg_match($mobilePattern, $mobile_number) === 1) {
        $returnval = 1;
    } else {
        $returnval = 0;
    }
    return $returnval;
}

function array_to_csv($array, $filename = "export.csv", $headers = null)
{
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    $fh = fopen('php://output', 'w');
    fputcsv($fh, $headers);
    foreach ($array as $row) {
        fputcsv($fh, $row);
    }
    fclose($fh);
}

function session_user_id()
{
    return session('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d');
}
function requiredParams($encryptData = '', $request = null, $index = null)
{
    $data['urlName'] = getCurrentURL($request, $index);
}
function getCurrentURL($request, $index)
{
    $routeArray = $request->path();
    $uses = explode('/', $routeArray);
    if (isset($index)) {
        return $uses[$index];
    } else {
        return $uses;
    }
}

function decryptToArray($encryptData)
{
    $decryptData = Crypt::decryptString($encryptData);
    $decode_array = json_decode($decryptData, true);
    if (is_array($decode_array)) {
        return $decode_array;
    } else {
        return ['id' => $decode_array];
    }
}
function createnewPassword($length = '')
{
    if (!empty($length)) {
        $length = $length;
    } else {
        $length = 8;
    }
    $alphabet = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$*';
    $pass = array(); // remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    $password = implode($pass);
    return $password;
}
function subtypeTable($type = '')
{
    $tables = ['agrmnt_type' => 'tbl_agreement_types', 'agrmnt_subtype' => 'tbl_agreement_subtypes', 'customer_id' => 'tbl_customer_salesorg_mapping', 'agency_id' => 'tbl_salesorg_agency_mapping'];
    return $tables[$type];
}
function subtypeWhereColumn($type = '')
{
    $column = ['agrmnt_subtype' => 'agrmnt_type_id', 'customer_id' => 'salesorganization_id', 'agency_id' => 'salesorganization_id'];
    return $column[$type];
}
function subtypeJoinTable($type = '')
{
    $jTable = ['agency_id' => 'tbl_agencies', 'customer_id' => 'tbl_customers'];
    if (array_key_exists($type, $jTable)) {
        return $jTable[$type];
    } else {
        return false;
    }
}
function subtypeJoinColumn($type = '')
{
    $jColumn = ['agency_id' => 'description', 'customer_id' => 'customer_name', 'organization' => 'sales_org_name', 'brand_id' => 'brand_name', 'product_id' => 'material_description','user_id'=>'first_name'];
    if (array_key_exists($type, $jColumn)) {
        return $jColumn[$type];
    } else {
        return false;
    }
}
function permissionexists($module)
{
    $modules = session()->get('permissiontypes');
    if (empty($modules) || !is_array($modules)) {
        return '0';
    }
    // Trim all permission values and the module value for comparison
    $modules = array_map('trim', $modules);
    $module = trim($module);
    if (in_array($module, $modules, true)) {
        $res = '1';
    } else {
        $res  = '0';
    }
    
    return $res;
}

/**
 * Reload permissions for the current logged-in user
 * Updates session permissions from database based on user's current role
 * 
 * @return bool True if permissions were reloaded, false otherwise
 */
if (!function_exists('reloadCurrentUserPermissions')) {
    function reloadCurrentUserPermissions()
    {
        try {
            if (!\Illuminate\Support\Facades\Auth::check()) {
                return false;
            }

            $user = \Illuminate\Support\Facades\Auth::user();
            $userRoleId = \Illuminate\Support\Facades\Session::get('effective_role_id', $user->user_type ?? null);

            if (empty($userRoleId)) {
                return false;
            }

            // Get permissions for the user's role from database
            $permissiontypes = \App\Models\Common_model::getDataFromTable(
                'tbl_roles',
                ['permission_types'],
                ['id' => $userRoleId],
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
                // Trim all permission values
                $permissions = array_map('trim', $permissions);
                \Illuminate\Support\Facades\Session::put('permissiontypes', $permissions);
                \Illuminate\Support\Facades\Log::info('Permissions reloaded for current user', [
                    'user_id' => $user->id ?? $user->user_id ?? null,
                    'role_id' => $userRoleId,
                    'permissions_count' => count($permissions)
                ]);
                return true;
            } else {
                // Clear permissions if role has none
                \Illuminate\Support\Facades\Session::put('permissiontypes', []);
                \Illuminate\Support\Facades\Log::warning('No permissions found for role, cleared session permissions', [
                    'user_id' => $user->id ?? $user->user_id ?? null,
                    'role_id' => $userRoleId
                ]);
                return false;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Reload Current User Permissions Error: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Check CMS permission with backward compatibility
 * Checks both new simplified permission AND old granular permissions
 * 
 * @param string $module Simplified module name (e.g., 'cms_pages')
 * @param string $action Optional action (e.g., 'list', 'add', 'edit', 'delete')
 * @return string '1' if permission exists, '0' otherwise
 */
if (!function_exists('cmsPermissionExists')) {
    function cmsPermissionExists($module, $action = 'list')
    {
        // Check new simplified permission first (e.g., 'cms_pages')
        if (permissionexists($module) == '1') {
            return '1';
        }
        
        // Fallback: Check old granular permissions for backward compatibility
        // Note: CMS permissions removed as CMS is not used in waste management project
        $oldPermissions = [];
        
        // If specific action requested, check that specific permission
        if ($action !== 'list' && isset($oldPermissions[$module])) {
            $actionMap = [
                'add' => $oldPermissions[$module][1] ?? null,
                'creation' => $oldPermissions[$module][1] ?? null,
                'edit' => $oldPermissions[$module][2] ?? null,
                'delete' => $oldPermissions[$module][3] ?? null,
                'reorder' => $oldPermissions[$module][4] ?? null,
                'view' => $oldPermissions[$module][1] ?? null,
                'export' => $oldPermissions[$module][4] ?? null,
            ];
            
            // Check the specific permission for this action
            if (isset($actionMap[$action]) && $actionMap[$action] && permissionexists($actionMap[$action]) == '1') {
                return '1';
            }
            
            // If specific action permission not found, return '0' (don't grant access based on other permissions)
            return '0';
        }
        
        // For 'list' action, check if user has any of the old permissions (backward compatibility)
        if ($action === 'list' && isset($oldPermissions[$module])) {
            foreach ($oldPermissions[$module] as $oldPermission) {
                if ($oldPermission && permissionexists($oldPermission) == '1') {
                    return '1';
                }
            }
        }
        
        return '0';
    }
}

function moduleexists($data)
{
    $count = 0;
    $modules = session()->get('permissiontypes');
    if (empty($modules) || !is_array($modules)) {
        return 0;
    }
    // Trim all permission values for comparison
    $modules = array_map('trim', $modules);
    foreach ($data as $value) {
        $permissionValue = trim($value['value']);
        if (in_array($permissionValue, $modules, true)) {
            $count++;
        }
    }
    return $count;
}

/**
 * Check if user type can access frontend
 * Only checks against FRONTEND_USER_TYPES array
 * 
 * @param int $userType User type constant (role ID)
 * @return bool
 */
if (!function_exists('isFrontendUserType')) {
    function isFrontendUserType($userType)
    {
        // Ensure userType is an integer for strict comparison
        $userType = (int)$userType;
        return in_array($userType, FRONTEND_USER_TYPES, true);
    }
}

/**
 * Check if user type can access backend
 * If not in FRONTEND_USER_TYPES, then it's a backend user
 * This automatically includes all dynamically created roles from admin panel
 * 
 * @param int $userType User type constant (role ID)
 * @return bool
 */
if (!function_exists('isBackendUserType')) {
    function isBackendUserType($userType)
    {
        // If it's not a frontend user type, it's a backend user type
        return !isFrontendUserType($userType);
    }
}

/**
 * Get redirect path for user type
 * For waste management system, all users redirect to dashboard
 * 
 * @param int $userType User type constant (role ID)
 * @return string Redirect path
 */
if (!function_exists('getRedirectPathForUserType')) {
    function getRedirectPathForUserType($userType)
    {
        // Always return absolute project URL to avoid root-level redirects (e.g., http://localhost/dashboard).
        return getProjectUrl('dashboard');
    }
}

/**
 * Get available roles for user based on linked registrations
 * 
 * @param int $userType User type constant (role ID)
 * @param array $linkedRegistrations Linked registrations from session
 * @return array Available roles with their details
 */
/**
 * Get all available role IDs for a user
 * For waste management system, users only have their base role
 * 
 * @param int $userType User's base role ID
 * @param array $linkedRegistrations Linked registrations array (not used in waste management)
 * @return array Array of role IDs
 */
if (!function_exists('getAvailableRoleIdsForUser')) {
    function getAvailableRoleIdsForUser($userType, $linkedRegistrations = [])
    {
        $roleIds = [];
        
        // Always include user's base role
        if (!empty($userType)) {
            $roleIds[] = $userType;
        }
        
        // Remove duplicates and return
        return array_unique($roleIds);
    }
}

/**
 * Get available roles with details for user
 * Returns actual role information from tbl_roles for each available role
 * 
 * @param int $userType User's base role ID
 * @param array $linkedRegistrations Linked registrations array
 * @return array Array of roles with details
 */
if (!function_exists('getAvailableRolesForUser')) {
    function getAvailableRolesForUser($userType, $linkedRegistrations = [])
    {
        $availableRoles = [];
        
        // Get all available role IDs
        $roleIds = getAvailableRoleIdsForUser($userType, $linkedRegistrations);
        
        if (empty($roleIds)) {
            return $availableRoles;
        }
        
            // Fetch role details from database
            try {
                $roles = \Illuminate\Support\Facades\DB::table('tbl_roles')
                    ->select('id', 'role_name', 'role_description', 'permission_types')
                    ->whereIn('id', $roleIds)
                    ->where('status', ACTIVE)
                    ->get();
            
            // If no linked registrations provided, get from session for count
            if (empty($linkedRegistrations)) {
                $linkedRegistrations = session('linked_registrations', []);
            }
            
            foreach ($roles as $role) {
                $roleId = $role->id;
                
                // Keep redirect absolute and scoped to project base URL.
                $redirectPath = getProjectUrl('dashboard');
                $roleType = 'backend';
                $icon = 'ri-admin-line';
                $count = 0;
                
                $availableRoles[] = [
                    'role_id' => $roleId,
                    'role_name' => $role->role_name,
                    'role_description' => $role->role_description ?? '',
                    'type' => $roleType,
                    'name' => $role->role_name,
                    'description' => $role->role_description ?? 'Access ' . strtolower($role->role_name) . ' features',
                    'icon' => $icon,
                    'redirect' => $redirectPath,
                    'count' => $count
                ];
            }
            
            // Sort roles by ID
            usort($availableRoles, function($a, $b) {
                return $a['role_id'] <=> $b['role_id'];
            });
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Get Available Roles Error: ' . $e->getMessage());
            // Return empty array on error
        }
        
        return $availableRoles;
    }
}

/**
 * Check if user has multiple roles/registrations
 * 
 * @param int $userType User type constant (role ID)
 * @param array $linkedRegistrations Linked registrations from session
 * @return bool
 */
if (!function_exists('hasMultipleRoles')) {
    function hasMultipleRoles($userType, $linkedRegistrations = [])
    {
        $availableRoles = getAvailableRolesForUser($userType, $linkedRegistrations);
        return count($availableRoles) > 1;
    }
}

if (!function_exists('displayStatus')) {
    function displayStatus($status)
    {
        if ($status) {
            $class = ['Active' => 'badge rounded-pill badge-soft-success', 'Inactive' => 'badge rounded-pill badge-soft-danger', 'Approved' => 'badge rounded-pill badge-soft-success', 'Sent for Approval' => 'badge rounded-pill badge-soft-success', 'Pending' => 'badge rounded-pill badge-soft-info', 'Sales Assigned' => 'badge rounded-pill badge-soft-danger','Proposal Generated'=>'badge rounded-pill badge-soft-success', 'Change of Request' => 'badge badge-soft-warning','Draft'=>'badge rounded-pill badge-soft-primary','Open'=>'badge rounded-pill badge-soft-warning','Verified'=>'badge rounded-pill badge-soft-success']; 
            if (isset($class[$status])) {
                $c = $class[$status];
                return "<label class='$c'>" . $status . "</label>";
            } else {
                return $status;
            }
        } else {
            return $status;
        }
    }
}

if (!function_exists('getbaseUrl')) {
    function getbaseUrl()
    {
        return getProjectUrl('admin');
    }
}

if (!function_exists('getProjectUrl')) {
    /**
     * Build absolute URLs from APP_URL so subfolder installs always work.
     */
    function getProjectUrl($path = '')
    {
        $root = getProjectRootUrl();

        $path = ltrim((string) $path, '/');
        return $path === '' ? $root : ($root . '/' . $path);
    }
}

if (!function_exists('getProjectRootUrl')) {
    /**
     * Project root URL without trailing /public.
     * Example: APP_URL=http://localhost/pdts/public -> http://localhost/pdts
     */
    function getProjectRootUrl()
    {
        $configured = rtrim((string) config('app.config_server_root', ''), '/');
        if ($configured !== '') {
            return rtrim(preg_replace('#/public$#', '', $configured), '/');
        }

        if (defined('CONFIG_SERVER_ROOT') && CONFIG_SERVER_ROOT !== '') {
            return rtrim(CONFIG_SERVER_ROOT, '/');
        }

        $url = rtrim((string) config('app.url'), '/');
        if ($url === '') {
            $url = rtrim(url('/'), '/');
        }
        return rtrim(preg_replace('#/public$#', '', $url), '/');
    }
}

if (!function_exists('getAssetUrl')) {
    /**
     * Build URL for legacy root-level assets folder: <project-root>/assets/...
     */
    function getAssetUrl($path = '')
    {
        $root = getProjectRootUrl();
        $path = ltrim((string) $path, '/');

        if ($path === '' || $path === 'assets') {
            return $root . '/assets';
        }

        if (strpos($path, 'assets/') === 0) {
            return $root . '/' . $path;
        }

        return $root . '/assets/' . $path;
    }
}
if(!function_exists('getUserTypesOnIds')){
    function getUserTypesOnIds($role_id=''){
        $userTypes = ['9' => 'Salesman', '11' => 'SalesManager', '12' => 'BDM', '13' => 'Finance', '16' => 'Administrator','20'=>'Audit'];
        if (array_key_exists($role_id, $userTypes)) {
            return $userTypes[$role_id];
        } else {
            return false;
        }
    }
}
if(!function_exists('getOrderByColumn')){
    function getOrderByColumn($type = '')
    {
        $jColumn = [
            'agency_id' => 'agency_code', 
            'customer_id' => 'customer_code', 
            'organization' => 'sales_org_code', 
            'brand_id' => 'brand_code', 
            'product_id' => 'material_number',
            'user_id'=>'employee_code'
        ];
        if (array_key_exists($type, $jColumn)) {
            return $jColumn[$type];
        } else {
            return false;
        }
    }
}

if(!function_exists('getUserTypeLevels')){
    function getUserTypeLevels($user_type=''){
        $userTypes = ['Salesman' => 'L1', 'SalesManager' => 'L2', 'BDM' => 'L3', 'Finance' => 'L4', 'Administrator' => 'L5'];
        if (array_key_exists($user_type, $userTypes)) {
            return $userTypes[$user_type];
        } else {
            return false;
        }
    }
}

if(!function_exists('convertSingleQuoteString')){
    function convertSingleQuoteString($string){
        $array = array_unique(explode(',',$string));
        $result= implode(',', array_map(function($val){return sprintf("'%s'", $val);}, $array));
        return $result;
    }

}

/**
 * This function accepts frequency of a subservice and gives expiry dates as result
 * @param int $frequency
 * @return array $task_expiry
 */

if(!function_exists("get_expiry_date"))
{
    function get_expiry_date($frequency = "")
    {
        $task_expiry = array();
        if(!empty($frequency))
        {
            $service_days = floor(365/$frequency);
            if($service_days == 7)
            {
                $service_days = $service_days -1;
            }
            if($service_days < 7)
            {
                for($i = $service_days;$i>=0;$i--)
                {
                    $day = "days";
                    
                    if($i == 1)
                    {
                        $day = "day";
                    }
                    $task_expiry["+".$i." days"] = "After ".($i)." ".$day;
                    if($i == 0)
                    {
                        $day = "day";
                        $task_expiry["+".$i." days"] = "Same ".$day;
                    }                    
                }
            }

            if($frequency <= 12)
            {
                for($i = 6;$i>=0;$i--)
                {
                    $day = "days";
                    if($i == 1)
                    {
                        $day = "day";
                    }
                    $task_expiry["+".$i." days"] = "After ".($i)." ".$day;
                    if($i == 0)
                    {
                        $day = "day";
                        $task_expiry["+".$i." days"] = "Same ".$day;
                    }
                }
            }
        }
        

        return $task_expiry;
    }
}

/**
 * This function takes multidimensional array and columns to be merged array 
 * and gives output a associative array 
 * @param array,array $multidime_array,$columnto_merge
 * @return array associative array
 */

 if(!function_exists("array_comb_keys_with_diff_vals"))
 {
    function array_comb_keys_with_diff_vals($multidime_array = array(), $columnto_merge = array(), $multidiemkeys_array = array())
    {
        $associative_array = array();
        
        if(!empty($multidime_array) && !empty($columnto_merge))
        {
            foreach ($multidime_array as $index => $row) {
                foreach ($row as $column => $value) {
                    if(in_array($column,$columnto_merge))
                    {
                        if(array_key_exists($column,$multidiemkeys_array))
                        {
                            $associative_array[$column][$row[$multidiemkeys_array[$column]]] = $value;
                        }
                        else
                        {
                            $associative_array[$column][$index] = $value;
                        }
                        
                    }
                    else
                    {
                        $associative_array[$column] = $value;
                    }
                }
            }
        }
        
        return $associative_array;
    }
}


/**
 * Prepare Select Option Html from the table data which was given as paramter
 * @param object,array (tableObjectdata) 
 * @return void html content
 * 
 */
if(!function_exists("prepare_options"))
{
    function prepare_options($tableObject,$selected_id,$options = array())
    {
        $errors = false;
        $table_data_options = [];
        $html = "";

        if(!is_object($tableObject))
        {
            $errors = true;
            throw new Exception("Provided tableObject parameter is not an object");
        }

        if(empty($options) || !is_array($options))
        {
            $errors = true;
            throw new Exception("provided options parameter is not any array or is an empty array");
        }
        
        if(!$errors)
        {
            $html = '<option value="">--Select--</option>';
            array_push($table_data_options,$html);
            foreach ($tableObject as $key => $object) {
                $html = '<option value="'.$object->{$options[0]}.'" >'.$object->{$options[1]}.'</option>';
                if($selected_id == $object->{$options[0]})
                {
                    $html = '<option value="'.$object->{$options[0]}.'" selected >'.$object->{$options[1]}.'</option>';
                }
                array_push($table_data_options,$html);
            }
        }
        $table_data_options = join(' ',$table_data_options);
        return $table_data_options;
    }
}

if(!function_exists("daysBetween"))
{
    function daysBetween($dt1, $dt2) 
    {
        $date11 = strtotime($dt1);
        $date22 = strtotime($dt2);
        $diff = $date22 - $date11;
        $diff_in_days = floor($diff/(60*60*24)) + 1;
        return $diff_in_days;
    }
}

if(!function_exists("weekdays"))
{
    function weekdays() 
    {
        $weekDays = array('Sunday'=>'Sunday','Monday'=>'Monday','Tuesday'=>'Tuesday','Wednesday'=>'Wednesday','Thursday'=>'Thursday','Friday'=>'Friday','Saturday'=>'Saturday');
        return $weekDays;
    }
}

if(!function_exists("displayCustomTime"))
{
    function displayCustomTime($time)
    {
        if ($time != '') {
            $time_new = new DateTime($time);
            //echo $time_new->format( 'g:i A' );
            return $time_new->format( 'g:i A' );
        } else {
            return '';
        }
    }
    
}

if(!function_exists("distance"))
{
    function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        if(!empty($lat1) && !empty($lon1) && !empty($lat2) && !empty($lon2)){
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  
                    cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);            
            if ($unit == "K") {
                return number_format(($miles * 1.609344), 2, '.', ',');
            } else if ($unit == "N") {
                return number_format(($miles * 0.8684), 2, '.', ',');
            } else {
                return number_format($miles, 2, '.', ',');
            }
        } else {
            return "";
        }   
    }
}

if(!function_exists("distanceNew"))
{
    function distanceNew($lat1, $lon1, $lat2, $lon2,$unit = '',$earthRadius = 6371){
        // Convert from degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine formula
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) +
                cos($lat1) * cos($lat2) *
                sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Distance in kilometers
        return $distance = $earthRadius * $c;
        // $miles = $distance * 60 * 1.1515;
        // $unit = strtoupper($unit);            
        // if ($unit == "K") {
        //     return number_format(($miles * 1.609344), 2, '.', ',');
        // } else if ($unit == "N") {
        //     return number_format(($miles * 0.8684), 2, '.', ',');
        // } else {
        //     return number_format($miles, 2, '.', ',');
        // }
    }
}

function isWeekend($date,$weekoffs) {
    $weeknumber = date('N', strtotime($date));
}

function convertWeekNamesToNumbers($weeknames = ''){
    $daynumbers = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thrusday'=>4,'Friday'=>5,'Saturday'=>6,'Sunday'=>0];
    $numbers = [];
    if(is_array($weeknames) && count($weeknames)>0){
        for($i=0;$i<count($weeknames);$i++){
            $numbers[] = $daynumbers[$weeknames[$i]];
        }
    }else if($weeknames!=''){
        $numbers[] = (isset($daynumbers[$weeknames])) ? $daynumbers[$weeknames] : '';
    }
    return $numbers;
}

function checkWeekOff($day,$month,$weekoffs){
    $date = date("Y").'-'.$month.'-'.$day;
    $dayOfWeekNumber = date('w', strtotime($date));
    if(is_array($weekoffs) && in_array($dayOfWeekNumber,$weekoffs)){
        return true;
    }else{
        return false;
    }
}

function checkHoliday($day,$month,$holidays){
    $date = date("Y").'-'.$month.'-'.$day;
    if(is_array($holidays) && in_array($date,$holidays)){
        return true;
    }else{
        return false;
    }
}

function checkAttendance($checkindex,$attendance){
    //$date = date("Y").'-'.$month.'-'.$day;
    if(is_array($attendance) && count($attendance)>0 && isset($attendance[$checkindex])){
        $dayattendance = $attendance[$checkindex];
        if($dayattendance['attendance_status'] == 1 && ($dayattendance['leave_id']==null || $dayattendance['leave_id']=='')){
            $class = 'text-success';
        }else if($dayattendance['attendance_status'] == 2 && ($dayattendance['leave_id']!=null || $dayattendance['leave_id']!='') && ($dayattendance['day_type'] == FULLDAY || $dayattendance['day_type']==null)){
            $class = 'text-dark';
        }else if($dayattendance['attendance_status'] == 2 && ($dayattendance['leave_id']!=null || $dayattendance['leave_id']!='') && $dayattendance['day_type'] != FULLDAY){
            $class = 'text-halfDay';
        }else{
            $class = 'leaveBg';
        }
    }else{
        $class = 'leaveBg';
    }
    return $class;
}

if(!function_exists("process_base64_photo"))
{
    function process_base64_photo($base64_str,$image_name = "",$path = "")
    {
        $result = array();
        try{
            $image_parts = explode(";base64,",$base64_str);
            $image = str_replace('data:image/jpeg;base64,','', $base64_str);
            $image = str_replace(' ', '+', $image);
            $image_content = mime_content_type($base64_str);
            $image_type = explode("/",$image_content);
            $imageName = $image_name.'_'.rand(9999,999999).'.'.$image_type[1];
            if(is_dir($path))
            {
                $image_base64 = base64_decode($image_parts[1]);
                file_put_contents($path.$imageName,$image_base64);
            }
            else
            {
                mkdir($path,0777, true);
                $image_base64 = base64_decode($image_parts[1]);
                file_put_contents($path.$imageName,$image_base64);
            }
            $result["image"] = $imageName;
            $result["error"] = false;
            $result["message"] = "Image uploaded & compressed successfully";
        }catch(\Exception $expection)
        {
            $result["image"] = "";
            $result["error"] = true;
            $result["message"] = $expection->getMessage();
        }        

        return $result;
    }
}

function getHoursDiffWithTime($startTime,$endTime){
    $startTime = new DateTime($startTime); // Example start time
    $endTime = new DateTime($endTime);   // Example end time
    $interval = $startTime->diff($endTime);
    $hours = $interval->format('%h');
    $minutes = $interval->format('%i');
    return $hours.':'.$minutes;
}

function convertMinutesToTime($minutes){
    $hours = floor($minutes / 60);
    $min = $minutes - ($hours * 60);
    return $hours.":".$min;
}

if(!function_exists("custom_money_format"))
{
    function custom_money_format($amount) {
        // Ensure the number has two decimal places and format with commas
        $formatted_amount = number_format(abs($amount), 2);
    
        // Add parentheses if the number is negative
        if ($amount < 0) {
            $formatted_amount = '(' . $formatted_amount . ')';
        }
    
        // Ensure at least 10 characters wide
        $formatted_amount = str_pad($formatted_amount, 10, ' ', STR_PAD_LEFT);
    
        // Add AED symbol
        $formatted_amount = 'AED ' . $formatted_amount;
    
        return $formatted_amount;
    }   
   
}

/**
 * @param array internal areas
 * @return string html
 */
if(!function_exists("generate_internal_areas_html"))
{
    function generate_internal_areas_html($internal_areas)
    {
        $html = '<table style="width:100%;border:1px solid black;border-collapse:collapse">';
        if(!empty($internal_areas))
        {
            foreach($internal_areas as $internal_area)
            {
                $html .= '<tr><td style="border:1px solid black"><img
                    style="width:200px;height:150px;padding-right:20px;text-align:center"
                    src='.url('uploads/internal_areas/'.$internal_area['area_image']).' alt='.$internal_area['area_image'].'></td>';
					if($internal_area['phrase_id'] == STANDARD_PHRASE_OTHERS){
						$html .= '<td>'.$internal_area['name'].'</td></tr>';
					}else{
						$html .= '<td>'.str_replace("##REPLACE_STRING##", $internal_area['name'], $internal_area['description']).'</td></tr>';
					}
            }
        }
        else
        {
            $html .= '<tr><td colspan=2><h4>No Internal Areas For This Service<h4></td></tr>';
        }
        $html .= '</table>';

        return $html;
    }
}

/**
 * @param array external areas
 * @return string html
 */
if(!function_exists("generate_external_areas_html"))
{
    function generate_external_areas_html($external_areas)
    {
        $html = '<table style="width:100%;border:1px solid black;border-collapse:collapse">';
        if(!empty($external_areas))
        {
            foreach($external_areas as $external_area)
            {
                $html .= '<tr><td style="border:1px solid black"><img
                    style="width:200px;height:150px;padding-right:20px;text-align:center"
                    src='.url('uploads/internal_areas/'.$external_area['area_image']).' alt='.$external_area['area_image'].'></td>';
					if($external_area['phrase_id'] == STANDARD_PHRASE_OTHERS){
						$html .= '<td>'.$external_area['name'].'</td></tr>';
					}else{
						$html .= '<td>'.str_replace("##REPLACE_STRING##", $external_area['name'], $external_area['description']).'</td></tr>';
					}
            }
        }
        else
        {
            $html .= '<tr><td colspan=2><h4>No External Areas For This Service<h4></td></tr>';
        }
        $html .= '</table>';

        return $html;
    }
}

/**
 * @param array included services
 * @return string html
 */
if(!function_exists("generate_included_service_html"))
{
    function generate_included_service_html($services,$included_services)
    {
        $li = '';
        $html = '';
        if(!empty($services))
        {
            foreach($services as $service)
            {
                if(in_array($service["id"],$included_services))
                {
                    $li .= '<li>'.$service["service_name"].'</li>';
                }
            }
            if(!empty($li))
            {
                $html = '<ul>'.$li.'</ul>';
            }
        }
        else
        {
            $html = '<ul>'.$li.'</ul>';
        }

        return $html;
    }
}

/**
 * @param array excluded services
 * @return string html
 */
if(!function_exists("generate_excluded_service_html"))
{
    function generate_excluded_service_html($excluded_services)
    {
        $li = '';
        $html = '';
        if(!empty($excluded_services))
        {
            foreach($excluded_services as $service)
            {
                $li .= '<li>'.$service["service_name"].'</li>';
            }
            if(!empty($li))
            {
                $html = '<ul>'.$li.'</ul>';
            }
        }
        else
        {
            $html = '<ul>'.$li.'</ul>';
        }

        return $html;
    }
}

/**
 * @param array services
 * @return string html
 */
if(!function_exists("generate_service_html"))
{
    function generate_service_html($service_charges)
    {
        $html = '<table style="width:100%;border:1px solid black;border-collapse:collapse">';
        $html .= '<tr><th>Service</th><th>Treatment Type</th><th>Frequency</th><th>Per Annum</th></tr>';
        if(!empty($service_charges))
        {
           
            foreach($service_charges as $service_charge)
            {
                $html .= "<tr>";
                $html .= '<td style="border:1px solid black">'.$service_charge['service_name'].'</td>';
                $html .= '<td style="border:1px solid black">'.$service_charge['treatment_name'].'</td>';
                $html .= '<td style="border:1px solid black">'.$service_charge['frequency'].'</td>';
                $html .= '<td style="border:1px solid black">'.$service_charge['total_price'].'</td>';
                $html .= "</tr>";
            }
            
        }
        else
        {
            $html .= '<tr><td colspan=3><h4>No Data Found<h4></td></tr>';
        }
        $html .= '</table>';

        return $html;
    }
}


function getHoursDiffWithTimeNew($startTime, $endTime)
{
     // Normalize time strings
     $startTime = normalizeTimeString($startTime);
     $endTime = normalizeTimeString($endTime);
 
     // Convert to DateTime objects
     try {
         $startTimeObj = new DateTime($startTime);
         $endTimeObj = new DateTime($endTime);
     } catch (Exception $e) {
         return json_encode(["error" => 1, "msg" => "Failed to parse time string: " . $e->getMessage()]);
     }
 
     // Calculate the interval
     $interval = $startTimeObj->diff($endTimeObj);
     $hours = $interval->format('%h');
     $minutes = $interval->format('%i');
     $seconds = $interval->format('%s');
 
     // Return the difference in hours, minutes, and seconds
     return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

function normalizeTimeString($time) 
{
      // Check if the time includes milliseconds
    if (strpos($time, '.') !== false) {
        // Split time into parts before and after the decimal point
        list($timePart, $milliseconds) = explode('.', $time);
        // Ensure the time part is correctly formatted (HH:MM:SS)
        $parts = explode(':', $timePart);
        
        // Handle cases where the time might only be in "MM:SS" format
        if (count($parts) == 2) {
            $time = "00:$timePart"; // Assume hours are "00" if not provided
        } elseif (count($parts) == 1) {
            $time = "00:00:$timePart"; // If only seconds are provided, assume "00:00:SS"
        } else {
            $time = $timePart; // If already in HH:MM:SS format, leave as is
        }
        
        // Append the milliseconds if necessary (ignore for DateTime purposes)
    } else {
        // Ensure the time is correctly formatted (HH:MM:SS)
        $parts = explode(':', $time);

        if (count($parts) == 2) {
            $time = "00:$time"; // Add hours as "00" if missing
        } elseif (count($parts) == 1) {
            $time = "00:00:$time"; // Add hours and minutes as "00" if missing
        }
    }
    return $time;
}

// function CheckReportGeneration($lastGeneratedOn,$type){
//     $currentDate = current_date();
//     $GenerateReport['generate'] = false;
//     if($type == 'Weekly'){
//         $currentDay = date('D');
//         if($currentDay == 'Mon'){
//             $GenerateReport['generate'] = true;
//             $GenerateReport['from_date'] = date('Y-m-d', strtotime('-7 days', strtotime($currentDate)));
//             $GenerateReport['to_date'] = date('Y-m-d', strtotime('-1 days', strtotime($currentDate)));
//         }
//     }elseif($type == '15_days'){
//         $daysDiff = getDateDiff('DAYS',$lastGeneratedOn,$currentDate);
//         if($lastGeneratedOn==null || $daysDiff == 15){
//             $GenerateReport['generate'] = true;
//             $GenerateReport['from_date'] = date('Y-m-d', strtotime('-15 days', strtotime($currentDate)));
//             $GenerateReport['to_date'] = $currentDate;
//         }
//     }elseif($type == 'Monthly'){
//         if(date('d') == '01'){
//             $GenerateReport['generate'] = true;
//             $GenerateReport['from_date'] = date('Y-m-d', strtotime('-30 days', strtotime($currentDate)));
//             $GenerateReport['to_date'] = $currentDate;
//         }
//     }elseif($type == '60_days'){
//         $daysDiff = getDateDiff('DAYS',$lastGeneratedOn,$currentDate);
//         if($lastGeneratedOn==null || $daysDiff == 60){
//             $GenerateReport['generate'] = true;
//             $GenerateReport['from_date'] = date('Y-m-d', strtotime('-60 days', strtotime($currentDate)));
//             $GenerateReport['to_date'] = $currentDate;
//         }
//     }elseif($type == '90_days'){
//         $daysDiff = getDateDiff('DAYS',$lastGeneratedOn,$currentDate);
//         if($lastGeneratedOn==null || $daysDiff == 90){
//             $GenerateReport['generate'] = true;
//             $GenerateReport['from_date'] = date('Y-m-d', strtotime('-90 days', strtotime($currentDate)));
//             $GenerateReport['to_date'] = $currentDate;
//         }
//     }
    
//     return $GenerateReport;
// }

function CheckReportGeneration($lastGeneratedOn, $type)
{
    $currentDate = current_date();
    $GenerateReport = ['generate' => false];
    $daysMapping = [
        'Weekly' => 7,
        '15_days' => 15,
        'Monthly' => 30,
        '60_days' => 60,
        '90_days' => 90,
    ];
    if ($type == 'Weekly' && date('D') === 'Mon') {
        $GenerateReport['generate'] = true;
        $GenerateReport['from_date'] = date('Y-m-d', strtotime('-7 days', strtotime($currentDate)));
        $GenerateReport['to_date'] = date('Y-m-d', strtotime('-1 days', strtotime($currentDate)));
        return $GenerateReport;
    }
    else if (isset($daysMapping[$type])) {
        $daysInterval = $daysMapping[$type];
        $daysDiff = $lastGeneratedOn ? getDateDiff('DAYS', $lastGeneratedOn, $currentDate) : $daysInterval;
        if ($lastGeneratedOn === null || $daysDiff >= $daysInterval) {
            $GenerateReport['generate'] = true;
            $GenerateReport['from_date'] = date('Y-m-d', strtotime("-{$daysInterval} days", strtotime($currentDate)));
            $GenerateReport['to_date'] = $currentDate;
        }
    }
    return $GenerateReport;
}

function convertNumberToWords($number) {
    $words = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 
        80 => 'Eighty', 90 => 'Ninety'
    );

    if ($number < 21) {
        return $words[$number];
    } elseif ($number < 100) {
        return $words[($number - $number % 10)] . (($number % 10 != 0) ? '-' . $words[$number % 10] : '');
    } elseif ($number < 1000) {
        return $words[floor($number / 100)] . ' Hundred' . (($number % 100 != 0) ? ' and ' . convertNumberToWords($number % 100) : '');
    } elseif ($number < 1000000) {
        return convertNumberToWords(floor($number / 1000)) . ' Thousand' . (($number % 1000 != 0) ? ' ' . convertNumberToWords($number % 1000) : '');
    } elseif ($number < 1000000000) {
        return convertNumberToWords(floor($number / 1000000)) . ' Million' . (($number % 1000000 != 0) ? ' ' . convertNumberToWords($number % 1000000) : '');
    } else {
        return convertNumberToWords(floor($number / 1000000000)) . ' Billion' . (($number % 1000000000 != 0) ? ' ' . convertNumberToWords($number % 1000000000) : '');
    }
}

function convertAmountToWords($amount) {
    // Split the amount into dirhams and fils (2 decimal places)
    $dirhams = floor($amount);
    $fils = round(($amount - $dirhams) * 100);

    // Convert dirhams and fils to words
    $dirhamsInWords = convertNumberToWords($dirhams) . ' Dirhams';
    $filsInWords = ($fils > 0) ? ' and ' . convertNumberToWords($fils) . ' Fils' : '';

    return $dirhamsInWords . $filsInWords;
}

function convertMinutesIntoTimeFormat($spent_hours){
	$hoursInMinutes = (int)($spent_hours);
	$diffinmins = ($spent_hours - $hoursInMinutes);
	$hours = $hoursInMinutes / 60;
	if($diffinmins > 0){
	    $mins =  $diffinmins * 60;
	}else{
	    $mins = ($hours - floor($hours)) * 60;
	}
    return $formattedTime = sprintf('%02d:%02d', $hours, $mins);
}

function convertToSeconds($time) {
    $parts = explode(':', $time);
    
    // If there is a decimal in the seconds part, we split it to get seconds and milliseconds.
    if (strpos($parts[1], '.') !== false) {
        list($seconds, $milliseconds) = explode('.', $parts[1]);
        $milliseconds = '0.' . $milliseconds;
    } else {
        $seconds = $parts[1];
        $milliseconds = 0;
    }

    // Convert the time into total seconds
    return ($parts[0] * 60) + $seconds + $milliseconds;
}

function convertToTime($totalSeconds) {
    $minutes = floor($totalSeconds / 60);
    $seconds = $totalSeconds % 60;
    return sprintf('%02d:%04.1f', $minutes, $seconds);
}

/**
 * Check if a string is a valid base64-encoded string.
 *
 * @param string $string
 * @return bool
 */
function isBase64($string){
    // A quick check to see if the string is a base64 encoded blob
    if (base64_decode($string, true) === false) {
        return false;
    }
    // Check if it contains base64 valid characters
    return base64_encode(base64_decode($string, true)) === $string;
}

function convertNumberToMinutes($hours){
    $IntHours = (int)$hours;
    $FloorMinutes = $hours - $IntHours;
    $hoursInMinutes = $IntHours * 60;
    $minutes = $FloorMinutes * 60;
    return $hours = $hoursInMinutes + $minutes;
}

function convertMinutesToNumber($minutes) {
    $IntMinutes = (int)$minutes;
    $hours = floor($IntMinutes / 60); // Get full hours
    $remainingMinutes = $IntMinutes % 60; // Remaining minutes
    $decimal = $remainingMinutes / 60; // Convert remaining minutes to decimal
    return $hours + $decimal;
}


function numberToOrdinal($number) {
    // Handle special cases for numbers ending in 11-13
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }
    
    // Determine the suffix for the rest
    switch ($number % 10) {
        case 1:
            return $number . 'st';
        case 2:
            return $number . 'nd';
        case 3:
            return $number . 'rd';
        default:
            return $number . 'th';
    }
}

function timeInWords($time){
    // Split the time string into hours and minutes
    list($hours, $minutes) = explode(':', $time);
    // Convert to integers to remove leading zeros
    $hours = (int)$hours;
    $minutes = (int)$minutes;
    // Build the readable string
    $result = "{$hours} hour" . ($hours > 1 ? "s" : "") . " {$minutes} minute" . ($minutes > 1 ? "s" : "");
    return $result; // Outputs: 7 hours 1 minute
}

function convertMinutesIntoHoursAndMinutes($totalMinutes){
    $hours = intdiv($totalMinutes, 60); // Integer division for hours
    $minutes = $totalMinutes % 60;     // Remainder for minutes
    // Format the result
    return $time = sprintf("%d:%02d", $hours, $minutes);
}


function getKeyValuesFromMultiArray($dataArray,$key){
    $res = [];
    if(is_array($dataArray) && count($dataArray)>0){
        foreach($dataArray as $array){
            if(isset($array[$key])){
                $res[] = $array[$key];
            }
        }
        return $res;
    }else{
        return false;
    }
}

function getSettingsValue($settings=[],$keyToSearch = null){
    if(is_array($settings) && count($settings)>0){
        $result = array_filter($settings, function ($value, $key) use ($keyToSearch) {
            return $key === $keyToSearch;
        }, ARRAY_FILTER_USE_BOTH);
        return reset($result);
    }
}

function arabicPaymentTerms($index){
    $array = ['','','الدفعة الثانية وتستحق بتاريخ','الدفعة الثالثة وتستحق بتاريخ',
            'الدفعة الرابعة وتستحق بتاريخ ','الدفعة الخامسة وتستحق بتاريخ ',
            'الدفعة السادسة وتستحق بتاريخ ','الدفعة السابعة وتستحق بتاريخ ',
            'الدفعة الثامنة وتستحق بتاريخ ','الدفعة التاسعة وتستحق بتاريخ ',
            'الدفعة العاشرة وتستحق بتاريخ ','الدفعة الحادية عشر وتستحق بتاريخ ',
            'الدفعة الثانية عشر وتستحق بتاريخ '];
    return $array[$index];
}

function checkFrequency($frequency,$days){
    $false = 0;
    if(is_array($frequency) && count($frequency)>0){
        for($i=0;$i<count($frequency);$i++){
            if($frequency[$i] > $days){
                $false++;
            }
        }
    }
    return $false;
}

function convertDecimalHourToHM($tasks,$other_tasks) {
    $hours = floor($tasks);
    $fraction = $tasks - $hours;
    $minTotal = round($fraction * 100);
    $fraction_minutes = $minTotal % 60;
    $total_tasks_min = ( $hours * 60 ) + $fraction_minutes;
    $all_total_min =  $total_tasks_min +  $other_tasks;
    $hours = intdiv($all_total_min, 60); // Integer division for hours
    $minutes = $all_total_min % 60;     // Remainder for minutes
    return sprintf("%d.%02d", $hours, $minutes); 
}

function checkLeaveOrWeekoff($weekoffs,$holidays,$new_attendance_date,$new_attendance_day,$attendance_date){
    if (in_array($new_attendance_day, $weekoffs) || in_array($new_attendance_date, $holidays)) {
        $class = 'idleBg';
    }else{
        if($attendance_date > current_date()){
            $class = 'idleBg';
        }else{
            $class = 'leaveBg';
        }
    }
    return $class;
}

if(!function_exists("custom_aed_format"))
{
    function custom_aed_format($amount) {
        // Ensure the number has two decimal places and format with commas
        $formatted_amount = number_format(abs($amount), 2);
    
        // Add parentheses if the number is negative
        if ($amount < 0) {
            $formatted_amount = '(' . $formatted_amount . ')';
        }
    
        // Ensure at least 10 characters wide
        $formatted_amount = str_pad($formatted_amount, 10, ' ', STR_PAD_LEFT);
    
        return $formatted_amount;
    }   
}

/**
 * Get image URL from upload path
 * Normalizes the path to use PUBLIC_UPLOADS_PATH constant and returns the full URL
 * 
 * @param string $imagePath The image path (can be relative like 'uploads/...' or 'public/uploads/...')
 * @return string The full URL to the image
 */
if (!function_exists('getImageUrl')) {
    function getImageUrl($imagePath)
    {
        if (empty($imagePath)) {
            return '';
        }

        $normalizedPath = ltrim((string) $imagePath, '/');
        $publicUploadsPrefix = trim((string) PUBLIC_UPLOADS_PATH, '/');

        // Normalize persisted path to always start with PUBLIC_UPLOADS_PATH.
        if (strpos($normalizedPath, $publicUploadsPrefix . '/') !== 0) {
            if (strpos($normalizedPath, 'uploads/') === 0) {
                $normalizedPath = $publicUploadsPrefix . '/' . substr($normalizedPath, 8);
            } elseif (strpos($normalizedPath, 'public/uploads/') === 0) {
                $normalizedPath = $normalizedPath;
            } else {
                $normalizedPath = $publicUploadsPrefix . '/' . $normalizedPath;
            }
        }

        // Build absolute URL from project root to support subfolder installs.
        return rtrim(getProjectRootUrl(), '/') . '/' . ltrim($normalizedPath, '/');
    }
}

/**
 * Resolve stored upload path to an absolute filesystem path under public/.
 */
if (!function_exists('getUploadAbsolutePath')) {
    function getUploadAbsolutePath($imagePath)
    {
        if (empty($imagePath)) {
            return '';
        }

        $normalizedPath = ltrim((string) $imagePath, '/');
        if (strpos($normalizedPath, 'public/uploads/') === 0) {
            return public_path(substr($normalizedPath, 7));
        }
        if (strpos($normalizedPath, 'uploads/') === 0) {
            return public_path($normalizedPath);
        }

        $publicUploadsPrefix = trim((string) PUBLIC_UPLOADS_PATH, '/');
        if (strpos($normalizedPath, $publicUploadsPrefix . '/') === 0) {
            $relative = substr($normalizedPath, strlen($publicUploadsPrefix) + 1);

            return public_path('uploads/' . ltrim($relative, '/'));
        }

        return public_path('uploads/' . $normalizedPath);
    }
}

/**
 * Base64 data URI for embedding images in PDFs (DomPDF).
 */
if (!function_exists('getImageDataUriForPdf')) {
    function getImageDataUriForPdf($imagePath)
    {
        $absolutePath = getUploadAbsolutePath($imagePath);
        if ($absolutePath === '' || !is_file($absolutePath)) {
            return '';
        }

        $mime = @mime_content_type($absolutePath);
        if (empty($mime)) {
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $mime = $extension === 'png' ? 'image/png' : 'image/jpeg';
        }

        $contents = @file_get_contents($absolutePath);
        if ($contents === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}

/**
 * Render image HTML tag
 * Returns a complete <img> tag with proper URL and optional attributes
 * 
 * @param string $imagePath The image path
 * @param string $alt Alt text for the image
 * @param array $attributes Additional HTML attributes (e.g., ['style' => 'max-width: 200px;', 'class' => 'img-thumbnail'])
 * @return string The complete <img> HTML tag
 */
if (!function_exists('renderImage')) {
    function renderImage($imagePath, $alt = '', $attributes = [])
    {
        if (empty($imagePath)) {
            return '';
        }
        
        $imageUrl = getImageUrl($imagePath);
        
        if (empty($imageUrl)) {
            return '';
        }
        
        $attrString = '';
        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }
        
        $altAttr = !empty($alt) ? ' alt="' . htmlspecialchars($alt) . '"' : '';
        
        return '<img src="' . htmlspecialchars($imageUrl) . '"' . $altAttr . $attrString . ' />';
    }
}

/**
 * Generate Membership Serial Number
 * Format: WM/{id}{year_suffix}
 * - WM is the prefix (Waste Management)
 * - id is padded to at least 2 digits
 * - year_suffix is the last 2 digits of the year
 * 
 * Examples:
 * - ID 1 in 2025: WM/0125
 * - ID 9 in 2025: WM/0925
 * - ID 29 in 2025: WM/2925
 * - ID 100 in 2025: WM/10025
 * - ID 1 in 2026: WM/0126
 * 
 * @param int $id The primary ID of the membership registration
 * @param string|null $year Optional year (defaults to current year)
 * @return string The formatted serial number
 */
if (!function_exists('generateMembershipSerialNumber')) {
    function generateMembershipSerialNumber($id, $year = null)
    {
        // Use provided year or current year
        $year = $year ?? date('Y');
        
        // Get last 2 digits of year
        $yearSuffix = substr($year, -2);
        
        // Pad ID to at least 2 digits
        $paddedId = str_pad($id, 2, '0', STR_PAD_LEFT);
        
        // Format: WM/{paddedId}{yearSuffix}
        return 'WM/' . $paddedId .'/'. $yearSuffix;
    }
}

/**
 * Generate Service Request Application Number
 * Format: WM-SR-YYYY-XXXXXX
 * 
 * @return string
 */
if (!function_exists('generateFreeSurgeryApplicationNumber')) {
    function generateFreeSurgeryApplicationNumber()
    {
        $year = date('Y');
        $prefix = "WM-SR-{$year}-";
        
        $lastNumber = DB::table('tbl_free_surgery_applications')
            ->where('application_number', 'LIKE', $prefix . '%')
            ->orderBy('application_number', 'DESC')
            ->value('application_number');
        
        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, -6);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }
        
        return $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
    }
}

/**
 * Check if user has free surgery application permission
 * 
 * @param string $permission
 * @return bool
 */
if (!function_exists('freeSurgeryPermissionExists')) {
    function freeSurgeryPermissionExists($permission)
    {
        return permissionexists('free_surgery_applications_' . $permission);
    }
}

function showIfAllowed($key, $privacySettings, $value)
{
    return (isset($privacySettings[$key]) && $privacySettings[$key] == 1 && !empty($value))
        ? $value
        : 'N/A';
}

/**
 * Convert RGB/RGBA color to hexadecimal format
 * Handles rgb(r, g, b), rgba(r, g, b, a), and hex colors
 * 
 * @param string $color Color in RGB, RGBA, or hex format
 * @return string Color in hexadecimal format (#RRGGBB)
 */
if (!function_exists('convertColorToHex')) {
    function convertColorToHex($color)
    {
        if (empty($color)) {
            return '';
        }
        
        $color = trim($color);
        
        // If already hex format, return as is (with # prefix)
        if (preg_match('/^#?[0-9A-Fa-f]{6}$/', $color)) {
            return strpos($color, '#') === 0 ? $color : '#' . $color;
        }
        
        // Handle rgb(r, g, b) format
        if (preg_match('/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/i', $color, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }
        
        // Handle rgba(r, g, b, a) format (ignore alpha, convert to hex)
        if (preg_match('/rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*[\d.]+\)/i', $color, $matches)) {
            $r = intval($matches[1]);
            $g = intval($matches[2]);
            $b = intval($matches[3]);
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }
        
        // If format not recognized, return original
        return $color;
    }
}

/**
 * Feedback Field Type Helper Functions
 * Centralized access to feedback field type configuration
 */

/**
 * Get all feedback field types
 * 
 * @param bool $enabledOnly If true, returns only enabled field types (for dropdowns)
 * @return array Array of field types with their labels
 */
function getFeedbackFieldTypes($enabledOnly = false)
{
    $fieldTypes = config('feedback.field_types', []);
    $result = [];
    
    foreach ($fieldTypes as $key => $config) {
        if (!$enabledOnly || $config['enabled']) {
            $result[$key] = $config['label'];
        }
    }
    
    return $result;
}

/**
 * Get feedback field type label
 * 
 * @param string $fieldType The field type key
 * @return string The label for the field type, or the key if not found
 */
function getFeedbackFieldTypeLabel($fieldType)
{
    $fieldTypes = config('feedback.field_types', []);
    return $fieldTypes[$fieldType]['label'] ?? $fieldType;
}

/**
 * Get all feedback field type keys
 * 
 * @param bool $enabledOnly If true, returns only enabled field types
 * @return array Array of field type keys
 */
function getFeedbackFieldTypeKeys($enabledOnly = false)
{
    $fieldTypes = config('feedback.field_types', []);
    $result = [];
    
    foreach ($fieldTypes as $key => $config) {
        if (!$enabledOnly || $config['enabled']) {
            $result[] = $key;
        }
    }
    
    return $result;
}

/**
 * Check if a field type requires options
 * 
 * @param string $fieldType The field type key
 * @return bool True if the field type requires options
 */
function feedbackFieldTypeRequiresOptions($fieldType)
{
    $typesRequiringOptions = config('feedback.field_types_requiring_options', []);
    return in_array($fieldType, $typesRequiringOptions);
}

/**
 * Check if a field type supports validation rules
 * 
 * @param string $fieldType The field type key
 * @return bool True if the field type supports validation
 */
function feedbackFieldTypeSupportsValidation($fieldType)
{
    $typesWithValidation = config('feedback.field_types_with_validation', []);
    return in_array($fieldType, $typesWithValidation);
}

/**
 * Check if a field type is a rating type
 * 
 * @param string $fieldType The field type key
 * @return bool True if the field type is a rating type
 */
function isFeedbackRatingFieldType($fieldType)
{
    $ratingTypes = config('feedback.rating_field_types', []);
    return in_array($fieldType, $ratingTypes);
}

/**
 * Get field types formatted for DataTables/Grid filters
 * 
 * @param bool $enabledOnly If true, returns only enabled field types
 * @return array Array formatted as [['value' => key, 'label' => label], ...]
 */
function getFeedbackFieldTypesForFilter($enabledOnly = false)
{
    $fieldTypes = getFeedbackFieldTypes($enabledOnly);
    $result = [];
    
    foreach ($fieldTypes as $key => $label) {
        $result[] = ['value' => $key, 'label' => $label];
    }
    
    return $result;
}

function addBaseUrl($route)
{
    if (empty($route)) {
        return '';
    }

    $route = trim($route);

    // If already absolute URL (http or https or protocol-relative)
    if (preg_match('/^(https?:)?\/\//i', $route)) {
        return $route;
    }

    // Append base URL
    return rtrim(url('/'), '/') . '/' . ltrim($route, '/');
}

/**
 * Normalize lab verification to canonical DB values (YES/NO). Returns null if empty/unknown.
 */
if (!function_exists('normalizeInspectionLabVerification')) {
    function normalizeInspectionLabVerification($value): ?string
    {
        return null;
    }
}

if (!function_exists('formatInspectionLabVerificationForDisplay')) {
    function formatInspectionLabVerificationForDisplay($value): string
    {
        return $value === null || $value === '' ? '-' : (string) $value;
    }
}

if (!function_exists('inspectionLabVerificationFormOptions')) {
    function inspectionLabVerificationFormOptions(): array
    {
        return [];
    }
}

