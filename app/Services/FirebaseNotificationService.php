<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FirebaseNotificationService
{
    private $messaging;
    private $projectId;
    private $credentialsPath;

    public function __construct()
    {
        $this->initializeFirebase();
    }

    /**
     * Initialize Firebase connection
     * 
     * @return void
     * @throws \Exception
     */
    private function initializeFirebase()
    {
        // Use centralized configuration from constants.php
        $this->credentialsPath = defined('FIREBASE_CREDENTIALS_PATH') ? FIREBASE_CREDENTIALS_PATH : null;
        
        if (empty($this->credentialsPath) || !file_exists($this->credentialsPath)) {
            Log::error('Firebase credentials file not found', ['path' => $this->credentialsPath]);
            throw new \Exception('Firebase credentials file not found at: ' . ($this->credentialsPath ?? 'undefined'));
        }

        // Use project ID from constants.php (already extracted from JSON file)
        $this->projectId = defined('FIREBASE_PROJECT_ID') ? FIREBASE_PROJECT_ID : null;

        if (empty($this->projectId)) {
            throw new \Exception('Firebase project ID not found. Please check your Firebase credentials file.');
        }

        try {
            $factory = (new Factory)->withServiceAccount($this->credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
            throw new \Exception('Failed to initialize Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Core method to send notification to a single device
     * 
     * @param string $deviceToken FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options (priority, sound, etc.)
     * @return bool
     */
    public function sendToDevice($deviceToken, $title, $body, $data = [], $options = [])
    {
        try {
            if (empty($deviceToken)) {
                return false;
            }

            // Detect platform from token or options
            $platform = $options['platform'] ?? $this->detectPlatformFromToken($deviceToken);

            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);

            // Add data payload if provided
            // Ensure all data values are strings (FCM requirement)
            if (!empty($data)) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[$key] = (string)$value;
                }
                $message = $message->withData($stringData);
            }

            // Add platform-specific configuration for mobile apps
            // Android configuration
            if ($platform === 'android' || $platform === null) {
                // Use provided Android config or create default
                if (!empty($options['android'])) {
                    $message = $message->withAndroidConfig($options['android']);
                } else {
                    // Default Android config for mobile notifications
                    try {
                        $androidConfig = AndroidConfig::new()
                            ->withSound('default');
                        $message = $message->withAndroidConfig($androidConfig);
                    } catch (\Exception $e) {
                        // Continue without Android config - Firebase will use defaults
                    }
                }
            }

            // iOS configuration
            if ($platform === 'ios') {
                // Use provided APNS config or create default
                if (!empty($options['apns'])) {
                    $message = $message->withApnsConfig($options['apns']);
                } else {
                    // Default iOS config for mobile notifications
                    try {
                        $apnsConfig = ApnsConfig::new()
                            ->withSound('default')
                            ->withBadge(1);
                        $message = $message->withApnsConfig($apnsConfig);
                    } catch (\Exception $e) {
                        // Continue without APNS config - Firebase will use defaults
                    }
                }
            }

            // Send the message
            $this->messaging->send($message);
            
            return true;
        } catch (\Kreait\Firebase\Exception\Messaging\InvalidArgument $e) {
            Log::error('Firebase invalid argument error: ' . $e->getMessage(), [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Invalid device token - should be removed from database
            Log::warning('Firebase device token not found (invalid token): ' . $e->getMessage(), [
                'device_token' => substr($deviceToken, 0, 20) . '...'
            ]);
            // Mark token as inactive in database
            $this->markTokenAsInactive($deviceToken);
            return false;
        } catch (\Kreait\Firebase\Exception\Messaging\AuthenticationError $e) {
            // SenderId mismatch - token was registered with a different Firebase project
            // This happens when switching Firebase projects
            Log::warning('Firebase SenderId mismatch (token from different project): ' . $e->getMessage(), [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'error' => $e->getMessage()
            ]);
            // Mark token as inactive - user needs to re-register with new project
            $this->markTokenAsInactive($deviceToken);
            return false;
        } catch (\Exception $e) {
            // Check if error message contains "SenderId mismatch" or "Invalid registration token"
            $errorMessage = $e->getMessage();
            if (stripos($errorMessage, 'SenderId mismatch') !== false || 
                stripos($errorMessage, 'Invalid registration token') !== false ||
                stripos($errorMessage, 'MismatchSenderId') !== false) {
                Log::warning('Firebase token error (invalid for current project): ' . $errorMessage, [
                    'device_token' => substr($deviceToken, 0, 20) . '...',
                    'error_class' => get_class($e)
                ]);
                // Mark token as inactive
                $this->markTokenAsInactive($deviceToken);
                return false;
            }
            
            Log::error('Firebase notification error: ' . $errorMessage, [
                'device_token' => substr($deviceToken, 0, 20) . '...',
                'error' => $errorMessage,
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Detect platform from device token by checking database
     * 
     * @param string $deviceToken
     * @return string|null Platform (android/ios/web) or null if not found
     */
    private function detectPlatformFromToken($deviceToken)
    {
        try {
            $tokenRecord = DB::table('tbl_user_device_tokens')
                ->where('device_token', $deviceToken)
                ->where('status', ACTIVE)
                ->select('platform')
                ->first();
            
            return $tokenRecord ? $tokenRecord->platform : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mark device token as inactive (when token is invalid)
     * 
     * @param string $deviceToken
     * @return void
     */
    private function markTokenAsInactive($deviceToken)
    {
        try {
            DB::table('tbl_user_device_tokens')
                ->where('device_token', $deviceToken)
                ->update([
                    'status' => 0, // INACTIVE
                    'updated_on' => current_datetime()
                ]);
        } catch (\Exception $e) {
            Log::error('Error marking token as inactive: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to multiple devices
     * 
     * @param array $deviceTokens Array of FCM device tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results with success/failure count
     */
    public function sendToMultipleDevices($deviceTokens, $title, $body, $data = [], $options = [])
    {
        if (empty($deviceTokens) || !is_array($deviceTokens)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }

        $success = 0;
        $failed = 0;
        $failedTokens = [];

        foreach ($deviceTokens as $token) {
            if (empty($token)) {
                continue;
            }
            
            if ($this->sendToDevice($token, $title, $body, $data, $options)) {
                $success++;
            } else {
                $failed++;
                $failedTokens[] = substr($token, 0, 20) . '...';
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => count($deviceTokens),
            'failed_tokens' => $failedTokens
        ];
    }

    /**
     * Send notification to user by user_id
     * Fetches all active device tokens for the user
     * 
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results
     */
    public function sendToUser($userId, $title, $body, $data = [], $options = [])
    {
        if (empty($userId)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'Invalid user ID'];
        }

        // Get all active device tokens for user with platform info
        $tokenRecords = DB::table('tbl_user_device_tokens')
            ->where('user_id', $userId)
            ->where('status', ACTIVE)
            ->select('device_token', 'platform')
            ->get();

        if ($tokenRecords->isEmpty()) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No device tokens found'];
        }

        // Update last_used_at
        DB::table('tbl_user_device_tokens')
            ->where('user_id', $userId)
            ->where('status', ACTIVE)
            ->update(['last_used_at' => current_datetime()]);

        // Send to each device with platform-specific options
        $success = 0;
        $failed = 0;
        foreach ($tokenRecords as $record) {
            $deviceOptions = array_merge($options, ['platform' => $record->platform]);
            if ($this->sendToDevice($record->device_token, $title, $body, $data, $deviceOptions)) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $tokenRecords->count()
        ];
    }

    /**
     * Build a CloudMessage for a device token
     * 
     * @param string $deviceToken FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options (platform, android, apns)
     * @return CloudMessage
     */
    private function buildMessage($deviceToken, $title, $body, $data = [], $options = [])
    {
        $notification = Notification::create($title, $body);
        
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        // Add data payload if provided
        if (!empty($data)) {
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[$key] = (string)$value;
            }
            $message = $message->withData($stringData);
        }

        // Add platform-specific configuration
        $platform = $options['platform'] ?? null;
        
        // Android configuration
        if ($platform === 'android' || $platform === null) {
            if (!empty($options['android'])) {
                $message = $message->withAndroidConfig($options['android']);
            } else {
                try {
                    $androidConfig = AndroidConfig::new()
                        ->withSound('default');
                    $message = $message->withAndroidConfig($androidConfig);
                } catch (\Exception $e) {
                    // Continue without Android config - Firebase will use defaults
                }
            }
        }

        // iOS configuration
        if ($platform === 'ios') {
            if (!empty($options['apns'])) {
                $message = $message->withApnsConfig($options['apns']);
            } else {
                try {
                    $apnsConfig = ApnsConfig::new()
                        ->withSound('default')
                        ->withBadge(1);
                    $message = $message->withApnsConfig($apnsConfig);
                } catch (\Exception $e) {
                    // Continue without APNS config - Firebase will use defaults
                }
            }
        }

        return $message;
    }

    /**
     * Send notifications to multiple devices using batch API (up to 500 per batch)
     * 
     * @param array $tokenRecords Array of objects with device_token and platform
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results with success/failure count and invalid tokens
     */
    private function sendBatch($tokenRecords, $title, $body, $data = [], $options = [])
    {
        $success = 0;
        $failed = 0;
        $invalidTokens = [];

        // Build messages for all tokens
        $messages = [];
        foreach ($tokenRecords as $record) {
            // Handle both object and array formats
            $deviceToken = is_object($record) ? $record->device_token : ($record['device_token'] ?? null);
            $platform = is_object($record) ? ($record->platform ?? null) : ($record['platform'] ?? null);
            
            if (empty($deviceToken)) {
                continue; // Skip if no token
            }
            
            $deviceOptions = array_merge($options, ['platform' => $platform]);
            try {
                $messages[] = $this->buildMessage($deviceToken, $title, $body, $data, $deviceOptions);
            } catch (\Exception $e) {
                // Skip invalid messages
            }
        }
        
        if (empty($messages)) {
            return [
                'success' => 0,
                'failed' => 0,
                'invalid_tokens' => []
            ];
        }

        // Firebase sendAll supports up to 500 messages per call
        // Split into batches of 500
        $batches = array_chunk($messages, 500);

        foreach ($batches as $batchIndex => $batch) {
            try {
                $report = $this->messaging->sendAll($batch);
                
                // Count successes
                $batchSuccess = $report->successes()->count();
                $success += $batchSuccess;
                
                // Process failures
                $failureReport = $report->failures();
                $batchFailed = $failureReport->count();
                $failed += $batchFailed;
                
                // Get invalid tokens and mark them as inactive
                $invalidTokensBatch = $failureReport->invalidTokens();
                foreach ($invalidTokensBatch as $invalidToken) {
                    $invalidTokens[] = $invalidToken;
                    $this->markTokenAsInactive($invalidToken);
                }
                
                // Also mark unknown tokens as inactive
                $unknownTokensBatch = $failureReport->unknownTokens();
                foreach ($unknownTokensBatch as $unknownToken) {
                    $invalidTokens[] = $unknownToken;
                    $this->markTokenAsInactive($unknownToken);
                }
            } catch (\Exception $e) {
                // If batch fails completely, fall back to individual sends for this batch
                foreach ($batch as $message) {
                    try {
                        $this->messaging->send($message);
                        $success++;
                    } catch (\Exception $sendError) {
                        $failed++;
                    }
                }
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'invalid_tokens' => $invalidTokens
        ];
    }

    /**
     * Send notification to multiple users
     * Uses batch sending for efficiency (up to 500 messages per API call)
     * 
     * @param array $userIds Array of user IDs
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results
     */
    public function sendToUsers($userIds, $title, $body, $data = [], $options = [])
    {
        if (empty($userIds) || !is_array($userIds)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'Invalid user IDs'];
        }

        // Get all active device tokens for users with platform info
        $tokenRecords = DB::table('tbl_user_device_tokens')
            ->whereIn('user_id', $userIds)
            ->where('status', ACTIVE)
            ->select('device_token', 'platform', 'user_id')
            ->get()
            ->unique('device_token') // Remove duplicate tokens
            ->values(); // Re-index array

        if ($tokenRecords->isEmpty()) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No device tokens found'];
        }

        // Convert collection to array of objects (not arrays) to maintain object property access
        $tokenRecordsArray = $tokenRecords->map(function($record) {
            return (object)[
                'device_token' => $record->device_token,
                'platform' => $record->platform,
                'user_id' => $record->user_id
            ];
        })->toArray();

        // Use batch sending instead of looping
        $result = $this->sendBatch($tokenRecordsArray, $title, $body, $data, $options);

        return [
            'success' => $result['success'],
            'failed' => $result['failed'],
            'total' => $tokenRecords->count()
        ];
    }

    /**
     * Send notification to users by role
     * 
     * @param int $userType User type/role constant
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results
     */
    public function sendToUserRole($userType, $title, $body, $data = [], $options = [])
    {
        // Get all user IDs with this role
        $userIds = DB::table('tbl_user')
            ->where('user_type', $userType)
            ->where('status', ACTIVE)
            ->where('is_mobile_enabled', ACTIVE)
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0, 'message' => 'No users found with this role'];
        }

        return $this->sendToUsers($userIds, $title, $body, $data, $options);
    }

    /**
     * Register/Update device token for user or anonymous
     * 
     * Supports both logged-in users and anonymous (non-logged-in) users.
     * When a user logs in and provides a device_token, any existing anonymous
     * token record for that device will be updated with the user_id.
     * 
     * @param int|null $userId User ID (null for anonymous users)
     * @param string $deviceToken FCM device token
     * @param string $deviceId Device unique ID
     * @param string $platform Platform (android/ios/web)
     * @param string $appVersion App version (optional)
     * @return bool
     */
    public function registerDeviceToken($userId, $deviceToken, $deviceId = null, $platform = 'android', $appVersion = null)
    {
        try {
            if (empty($deviceToken)) {
                return false;
            }

            $currentDateTime = current_datetime();

            // Strategy: Ensure only ONE active token per device_id
            // Priority: device_id > device_token
            // 1. If device_id is provided, check for existing tokens for that device_id
            // 2. Deactivate all other tokens for the same device_id
            // 3. Update or create the token record

            $existingByDeviceId = null;
            $existingByToken = null;

            // Priority 1: Check if this exact token already exists (most reliable match)
            // Check regardless of status - we'll reactivate if needed
            $existingByToken = DB::table('tbl_user_device_tokens')
                ->where('device_token', $deviceToken)
                ->first();

            // Priority 2: Check if device_id already has an active token (for mobile apps)
            // This ensures only one token per device
            if (!empty($deviceId)) {
                $existingByDeviceId = DB::table('tbl_user_device_tokens')
                    ->where('device_id', $deviceId)
                    ->where('status', ACTIVE)
                    ->first();
            }

            // Priority: Use token match first (most accurate), then device_id match
            // Token match takes precedence because it's the exact same token
            $existing = $existingByToken ?? $existingByDeviceId;

            // If device_id is provided, ensure only one active token per device
            if (!empty($deviceId)) {
                // Deactivate all other tokens for this device_id (except the one we're updating)
                $excludeId = $existing ? $existing->id : null;
                $deactivateQuery = DB::table('tbl_user_device_tokens')
                    ->where('device_id', $deviceId)
                    ->where('status', ACTIVE);
                
                if ($excludeId) {
                    $deactivateQuery->where('id', '!=', $excludeId);
                }
                
                $deactivateQuery->update([
                    'status' => 0, // INACTIVE
                    'updated_on' => $currentDateTime
                ]);
            }
            
            // Also deactivate any other tokens with the same device_token (duplicate prevention)
            // This handles cases where the same token might exist multiple times
            if ($existing) {
                DB::table('tbl_user_device_tokens')
                    ->where('device_token', $deviceToken)
                    ->where('id', '!=', $existing->id)
                    ->where('status', ACTIVE)
                    ->update([
                        'status' => 0, // INACTIVE
                        'updated_on' => $currentDateTime
                    ]);
            }

            if ($existing) {
                // Update the existing token record
                $updateData = [
                    'device_token' => $deviceToken, // Update token in case it changed
                    'status' => ACTIVE,
                    'device_id' => $deviceId ?? $existing->device_id,
                    'platform' => $platform,
                    'app_version' => $appVersion ?? $existing->app_version,
                    'last_used_at' => $currentDateTime,
                    'updated_on' => $currentDateTime,
                ];

                // Always update user_id if provided (even if it's 0, though that shouldn't happen)
                // This ensures tokens are properly linked to users during login
                if ($userId !== null) {
                    $updateData['user_id'] = $userId;
                }

                DB::table('tbl_user_device_tokens')
                    ->where('id', $existing->id)
                    ->update($updateData);
            } else {
                // No existing token found - create new one

                // Insert new token
                DB::table('tbl_user_device_tokens')->insert([
                    'user_id' => $userId, // Can be null for anonymous
                    'device_token' => $deviceToken,
                    'device_id' => $deviceId,
                    'platform' => $platform,
                    'app_version' => $appVersion,
                    'status' => ACTIVE,
                    'last_used_at' => $currentDateTime,
                    'created_on' => $currentDateTime,
                    'updated_on' => $currentDateTime,
                ]);
            }

            // Automatically subscribe device token to "all_users" topic for broadcast notifications
            // This allows sending to all users via topic messaging (no device tokens needed!)
            try {
                $this->subscribeToTopic($deviceToken, 'all_users');
            } catch (\Exception $topicError) {
                // Don't fail registration if topic subscription fails - continue silently
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error registering device token: ' . $e->getMessage(), [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'platform' => $platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Unregister/Deactivate device token
     * 
     * @param int|null $userId User ID (optional - if null, unregister by token only)
     * @param string $deviceToken FCM device token
     * @return bool
     */
    public function unregisterDeviceToken($userId, $deviceToken)
    {
        try {
            if (empty($deviceToken)) {
                return false;
            }

            $query = DB::table('tbl_user_device_tokens')
                ->where('device_token', $deviceToken);

            // If user_id is provided, also filter by it
            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }

            $query->update([
                'status' => INACTIVE,
                'updated_on' => current_datetime(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error unregistering device token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all active device tokens (for broadcast notifications)
     * 
     * @param bool $includeAnonymous Include anonymous (non-logged-in) users
     * @param string|null $platform Filter by platform (android/ios/web)
     * @return array Array of device tokens
     */
    public function getAllActiveDeviceTokens($includeAnonymous = true, $platform = null)
    {
        try {
            $query = DB::table('tbl_user_device_tokens')
                ->where('status', ACTIVE)
                ->select('device_token', 'user_id', 'platform');

            if (!$includeAnonymous) {
                $query->whereNotNull('user_id');
            }

            if (!empty($platform)) {
                $query->where('platform', $platform);
            }

            $results = $query->get();
            
            $tokens = $results->pluck('device_token')->toArray();
            return $tokens;
        } catch (\Exception $e) {
            Log::error('Error getting all device tokens: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Subscribe device token(s) to a topic
     * 
     * @param string|array $deviceTokens Single token or array of tokens
     * @param string $topic Topic name (e.g., 'all_users')
     * @return bool Success status
     */
    public function subscribeToTopic($deviceTokens, $topic = 'all_users')
    {
        try {
            if (empty($deviceTokens)) {
                return false;
            }

            // Convert single token to array
            $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];
            
            // Remove empty tokens
            $tokens = array_filter($tokens, function($token) {
                return !empty($token);
            });

            if (empty($tokens)) {
                return false;
            }

            $this->messaging->subscribeToTopic($topic, $tokens);
            return true;
        } catch (\Exception $e) {
            Log::error('Error subscribing to topic: ' . $e->getMessage(), [
                'topic' => $topic,
                'token_count' => is_array($deviceTokens) ? count($deviceTokens) : 1
            ]);
            return false;
        }
    }

    /**
     * Unsubscribe device token(s) from a topic
     * 
     * @param string|array $deviceTokens Single token or array of tokens
     * @param string $topic Topic name
     * @return bool Success status
     */
    public function unsubscribeFromTopic($deviceTokens, $topic = 'all_users')
    {
        try {
            if (empty($deviceTokens)) {
                return false;
            }

            // Convert single token to array
            $tokens = is_array($deviceTokens) ? $deviceTokens : [$deviceTokens];
            
            // Remove empty tokens
            $tokens = array_filter($tokens, function($token) {
                return !empty($token);
            });

            if (empty($tokens)) {
                return false;
            }

            $this->messaging->unsubscribeFromTopic($topic, $tokens);
            return true;
        } catch (\Exception $e) {
            Log::error('Error unsubscribing from topic: ' . $e->getMessage(), [
                'topic' => $topic,
                'token_count' => is_array($deviceTokens) ? count($deviceTokens) : 1
            ]);
            return false;
        }
    }

    /**
     * Send notification to a topic (no device tokens needed!)
     * This is the most efficient way to send to all users
     * 
     * @param string $topic Topic name (e.g., 'all_users')
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Additional options
     * @return array Results
     */
    public function sendToTopic($topic, $title, $body, $data = [], $options = [])
    {
        try {
            $notification = Notification::create($title, $body);
            
            // Build message targeting the topic
            $message = CloudMessage::new()
                ->toTopic($topic)
                ->withNotification($notification);

            // Add data payload if provided
            if (!empty($data)) {
                $stringData = [];
                foreach ($data as $key => $value) {
                    $stringData[$key] = (string)$value;
                }
                $message = $message->withData($stringData);
            }

            // Add platform-specific configuration
            $platform = $options['platform'] ?? null;
            
            // Android configuration
            if ($platform === 'android' || $platform === null) {
                if (!empty($options['android'])) {
                    $message = $message->withAndroidConfig($options['android']);
                } else {
                    try {
                        $androidConfig = AndroidConfig::new()
                            ->withSound('default');
                        $message = $message->withAndroidConfig($androidConfig);
                    } catch (\Exception $e) {
                        // Continue without Android config
                    }
                }
            }

            // iOS configuration
            if ($platform === 'ios') {
                if (!empty($options['apns'])) {
                    $message = $message->withApnsConfig($options['apns']);
                } else {
                    try {
                        $apnsConfig = ApnsConfig::new()
                            ->withSound('default')
                            ->withBadge(1);
                        $message = $message->withApnsConfig($apnsConfig);
                    } catch (\Exception $e) {
                        // Continue without APNS config
                    }
                }
            }

            // Send to topic - ONE API call for ALL subscribed devices!
            $result = $this->messaging->send($message);
            
            return [
                'success' => true,
                'sent' => true,
                'message' => 'Notification sent to topic: ' . $topic,
                'result' => $result
            ];
        } catch (\Exception $e) {
            Log::error('Error sending to topic: ' . $e->getMessage(), [
                'topic' => $topic,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to all registered devices (broadcast)
     * Uses topic messaging for maximum efficiency (ONE API call, no device tokens needed!)
     * Falls back to batch sending if topic subscription fails
     * 
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param bool $includeAnonymous Include anonymous users
     * @param string|null $platform Filter by platform
     * @param bool $useTopic Use topic messaging (recommended) or fall back to batch sending
     * @return array Results with success count and failures
     */
    public function sendToAllDevices($title, $body, $data = [], $includeAnonymous = true, $platform = null, $useTopic = true)
    {
        // Use topic messaging for maximum efficiency (ONE API call, no device tokens needed!)
        if ($useTopic) {
            try {
                // Count active device tokens to estimate how many devices will receive the notification
                // (All active tokens should be subscribed to 'all_users' topic)
                $query = DB::table('tbl_user_device_tokens')
                    ->where('status', ACTIVE);
                
                if (!$includeAnonymous) {
                    $query->whereNotNull('user_id');
                }
                
                if (!empty($platform)) {
                    $query->where('platform', $platform);
                }
                
                $totalDevices = $query->distinct('device_token')->count('device_token');
                
                $result = $this->sendToTopic('all_users', $title, $body, $data, ['platform' => $platform]);
                if ($result['success']) {
                    return [
                        'success' => true,
                        'sent' => $totalDevices, // Return actual count of subscribed devices
                        'failed' => 0,
                        'total' => $totalDevices, // Return actual count
                        'method' => 'topic',
                        'message' => 'Notification sent to all_users topic (' . $totalDevices . ' subscribed devices)'
                    ];
                }
            } catch (\Exception $e) {
                // Fall through to batch sending
            }
        }

        // Fallback to batch sending if topic messaging is disabled or fails
        try {
            // Get all active device tokens with platform info directly
            $query = DB::table('tbl_user_device_tokens')
                ->where('status', ACTIVE)
                ->select('device_token', 'platform', 'user_id');

            if (!$includeAnonymous) {
                $query->whereNotNull('user_id');
            }

            if (!empty($platform)) {
                $query->where('platform', $platform);
            }

            $tokenRecords = $query->get()
                ->unique('device_token') // Remove duplicate tokens
                ->values(); // Re-index array

            if ($tokenRecords->isEmpty()) {
                return [
                    'success' => true,
                    'sent' => 0,
                    'failed' => 0,
                    'total' => 0,
                    'message' => 'No active devices found'
                ];
            }

            // Convert collection to array of objects (not arrays) to maintain object property access
            $tokenRecordsArray = $tokenRecords->map(function($record) {
                return (object)[
                    'device_token' => $record->device_token,
                    'platform' => $record->platform,
                    'user_id' => $record->user_id
                ];
            })->toArray();

            // Use batch sending instead of looping
            $result = $this->sendBatch($tokenRecordsArray, $title, $body, $data, []);

            return [
                'success' => true,
                'sent' => $result['success'],
                'failed' => $result['failed'],
                'total' => $tokenRecords->count(),
                'method' => 'batch'
            ];
        } catch (\Exception $e) {
            Log::error('Error sending broadcast notification: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get active device tokens for a user
     * 
     * @param int $userId User ID
     * @return array Array of device tokens
     */
    public function getUserDeviceTokens($userId)
    {
        return DB::table('tbl_user_device_tokens')
            ->where('user_id', $userId)
            ->where('status', ACTIVE)
            ->pluck('device_token')
            ->toArray();
    }

    /**
     * Check if user has registered device tokens
     * 
     * @param int $userId User ID
     * @return bool
     */
    public function userHasDeviceTokens($userId)
    {
        return DB::table('tbl_user_device_tokens')
            ->where('user_id', $userId)
            ->where('status', ACTIVE)
            ->exists();
    }

    /**
     * Clean up inactive/expired device tokens (utility method)
     * 
     * @param int $daysInactive Number of days to consider token inactive (default: 90)
     * @return int Number of tokens cleaned up
     */
    public function cleanupInactiveTokens($daysInactive = 90)
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysInactive} days"));
            
            $deleted = DB::table('tbl_user_device_tokens')
                ->where('status', INACTIVE)
                ->where('updated_on', '<', $cutoffDate)
                ->delete();
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error cleaning up inactive tokens: ' . $e->getMessage());
            return 0;
        }
    }
}
