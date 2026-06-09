<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Web Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Web Push Notifications
    | These values are read from environment variables
    |
    */

    'api_key' => env('FIREBASE_WEB_API_KEY'),
    'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
    'project_id' => env('FIREBASE_WEB_PROJECT_ID'),
    'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET'),
    'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_WEB_APP_ID'),
    'measurement_id' => env('FIREBASE_WEB_MEASUREMENT_ID'),
    'vapid_key' => env('FIREBASE_WEB_VAPID_KEY'),
];

