<?php 

if (!defined('ACTIVE')) define('ACTIVE', 1);
if (!defined('INACTIVE')) define('INACTIVE', 2);

// User Types
if (!defined('ADMIN')) define('ADMIN', 1);

// Frontend User Types - Only define frontend users here
// All other roles (created from admin panel) will be considered backend users
if (!defined('FRONTEND_USER_TYPES')) {
    define('FRONTEND_USER_TYPES', []);
}

// Email Template IDs
if (!defined('EMAIL_TEMPLATE_WELCOME')) define('EMAIL_TEMPLATE_WELCOME', env('EMAIL_TEMPLATE_WELCOME', 1));
if (!defined('EMAIL_TEMPLATE_PASSWORD_CHANGE')) define('EMAIL_TEMPLATE_PASSWORD_CHANGE', env('EMAIL_TEMPLATE_PASSWORD_CHANGE', 3));
if (!defined('EMAIL_TEMPLATE_NEW_USER_ONBOARDED')) define('EMAIL_TEMPLATE_NEW_USER_ONBOARDED', env('EMAIL_TEMPLATE_NEW_USER_ONBOARDED', 4));
if (!defined('EMAIL_TEMPLATE_FORGOT_PASSWORD')) define('EMAIL_TEMPLATE_FORGOT_PASSWORD', env('EMAIL_TEMPLATE_FORGOT_PASSWORD', 2));
if (!defined('EMAIL_TEMPLATE_RESET_PASSWORD')) define('EMAIL_TEMPLATE_RESET_PASSWORD', env('EMAIL_TEMPLATE_RESET_PASSWORD', 14));

// OTP Configuration
if (!defined('OTP_EXPIRY_MINUTES')) define('OTP_EXPIRY_MINUTES', env('OTP_EXPIRY_MINUTES', 15));

// Admin Emails for Notifications (comma-separated)
if (!defined('ADMIN_NOTIFICATION_EMAILS')) define('ADMIN_NOTIFICATION_EMAILS', env('ADMIN_NOTIFICATION_EMAILS', ''));

// Public Uploads Path (relative to public folder, used for file storage and URL generation)
if (!defined('PUBLIC_UPLOADS_PATH')) define('PUBLIC_UPLOADS_PATH', env('PUBLIC_UPLOADS_PATH', 'public/uploads'));

// Canonical project root URL — resolved at runtime via config('app.config_server_root').
// Do not call env() here: this file is autoloaded before Laravel boots .env.
if (!defined('CONFIG_SERVER_ROOT')) {
    define('CONFIG_SERVER_ROOT', '');
}

// Firebase Configuration
// Centralized configuration - single source of truth
// Priority: FIREBASE_CREDENTIALS_PATH (env) > FIREBASE_CREDENTIALS_FILE (env) > default filename
if (!defined('FIREBASE_CREDENTIALS_PATH')) {
    // Check for full path in env first
    $credentialsPath = env('FIREBASE_CREDENTIALS_PATH');
    
    // If not set, use filename from env or default
    if (empty($credentialsPath)) {
        $credentialsFileName = env('FIREBASE_CREDENTIALS_FILE', '');
        if (!empty($credentialsFileName)) {
            $credentialsPath = __DIR__ . '/firebase/' . $credentialsFileName;
        } else {
            $credentialsPath = '';
        }
    } else {
        // If full path is provided, check if it's relative or absolute
        if (!file_exists($credentialsPath) && substr($credentialsPath, 0, 1) !== '/') {
            // Relative path - prepend config directory
            $credentialsPath = __DIR__ . '/' . ltrim($credentialsPath, '/');
        }
    }
    
    define('FIREBASE_CREDENTIALS_PATH', $credentialsPath);
}

// Read project ID from credentials file
if (!defined('FIREBASE_PROJECT_ID')) {
    $firebaseProjectId = null;
    $credentialsPath = FIREBASE_CREDENTIALS_PATH;
    
    if (!empty($credentialsPath) && file_exists($credentialsPath)) {
        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $firebaseProjectId = $credentials['project_id'] ?? null;
        } catch (\Exception $e) {
            // Silently fail - will be caught by services
        }
    }
    
    define('FIREBASE_PROJECT_ID', $firebaseProjectId);
}

?>
