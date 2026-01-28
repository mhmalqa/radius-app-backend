<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'radius' => [
        'api_url' => env('RADIUS_API_URL', 'http://38.156.75.137:3031'),
        'api_key' => env('RADIUS_API_KEY', 'APP2025M'),
        'renew_endpoint' => env('RADIUS_RENEW_ENDPOINT', '/radiusmanager/USERS/dash/renew_subscription.php'),
    ],

    'firebase' => [
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', 'storage/app/firebase/service-account-key.json'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],

    'live_stream' => [
        // Default lowered to reduce expiry-related playback cuts.
        // Can be overridden per environment via LIVE_STREAM_TOKEN_TTL_SECONDS.
        // 55 minutes = 3300 seconds
        'token_ttl_seconds' => env('LIVE_STREAM_TOKEN_TTL_SECONDS', 3300),
    ],

];
