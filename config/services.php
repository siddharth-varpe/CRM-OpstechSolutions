<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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
        'browser_key' => env('GOOGLE_MAPS_BROWSER_API_KEY', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCwOZfzJtiXB6-YezhDbrRX8NFN9Ftvuho')),
        'routes_key' => env('GOOGLE_MAPS_SERVER_API_KEY', env('ROUTES_API_KEY', 'AIzaSyDdTekgLDrfEgoE2Dnzc48A_Ew0gvn_7yc')),
        'map_id' => env('GOOGLE_MAPS_MAP_ID', 'ce26a3716462fb83393ece93'),
    ],

];
