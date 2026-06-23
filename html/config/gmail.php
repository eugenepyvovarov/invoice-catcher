<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gmail / Google OAuth Configuration
    |--------------------------------------------------------------------------
    */

    'project_id' => env('GOOGLE_PROJECT_ID'),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_url' => env('GOOGLE_REDIRECT_URI', '/oauth/gmail/callback'),

    /*
    |--------------------------------------------------------------------------
    | OAuth scopes (URL form; readonly is default)
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        'https://www.googleapis.com/auth/gmail.readonly',
    ],

    'additional_scopes' => [],

    'access_type' => 'offline',

    'prompt' => 'consent',

    /*
    |--------------------------------------------------------------------------
    | Credentials storage
    |--------------------------------------------------------------------------
    | Tokens are stored under storage/app/gmail/tokens/{prefix}-{userId}.json
    */
    'credentials_file_name' => env('GOOGLE_CREDENTIALS_NAME', 'gmail-json'),

    'allow_json_encrypt' => env('GOOGLE_ALLOW_JSON_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Allowed attachment extensions (comma-separated in env)
    |--------------------------------------------------------------------------
    */
    'allowed_extensions' => array_filter(array_map('trim', explode(',', env('GMAIL_ALLOWED_EXTENSIONS', env('GMAIl_ALLOWED_EXTENSIONS', 'pdf'))))),
];
