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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'nextpax' => [
        'client_id' => env('NEXTPAX_CLIENT_ID'),
        'client_secret' => env('NEXTPAX_CLIENT_SECRET'),
        'sender_id' => env('NEXTPAX_SENDER_ID'),
        'token' => env('NEXTPAX_TOKEN'),
        'supply_api_base' => env('NEXTPAX_SUPPLY_API_BASE'),
        'bookings_api_base' => env('NEXTPAX_BOOKINGS_API_BASE'),
        'messaging_api_base' => env('NEXTPAX_MESSAGING_API_BASE'),
        'auth_url' => env('NEXTPAX_AUTH_URL'),
    ],

];
