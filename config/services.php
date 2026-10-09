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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'zazu' => [
        'base_url' => env('ZAZU_BASE_URL'),
        'inventory_path' => env('ZAZU_INVENTORY_PATH', '/api/external/inventario'),
        'token' => env('ZAZU_INVENTORY_TOKEN'),
        'timeout' => (int) env('ZAZU_TIMEOUT', 30),
    ],

    // Probador virtual (https://docs.fashn.ai). Sin FASHN_API_KEY el probador funciona sin IA.
    'fashn' => [
        'api_key' => env('FASHN_API_KEY'),
        'base_url' => env('FASHN_BASE_URL', 'https://api.fashn.ai/v1'),
        'model' => env('FASHN_MODEL', 'tryon-v1.6'),
        'mode' => env('FASHN_MODE', 'balanced'),
        'timeout' => (int) env('FASHN_TIMEOUT', 20),
    ],

    'storefront' => [
        'live_url' => env('STOREFRONT_LIVE_URL'),
        'chat_url' => env('STOREFRONT_CHAT_URL'),
    ],

];
