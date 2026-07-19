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

    'languagetool' => [
        'driver' => env('LANGUAGETOOL_DRIVER', 'local'),
        'url' => env('LANGUAGETOOL_URL', 'http://host.docker.internal:8011'),
        'token' => env('LANGUAGETOOL_API_TOKEN'),
        'api_url' => env('LANGUAGETOOL_API_URL', 'https://api.languagetoolplus.com'),
        'username' => env('LANGUAGETOOL_API_USERNAME'),
        'api_key' => env('LANGUAGETOOL_API_KEY'),
    ],

    /*
    | AI editorial review ("KI-Lektorat"). Credentials for the underlying
    | provider live in config/ai.php (driven by the .env). These values select
    | which configured provider/model the review agent should use. When
    | "provider" is null the ai.default provider applies; when "model" is null
    | the provider's default text model applies.
    */
    'ai_lektorat' => [
        'enabled' => env('AI_LEKTORAT_ENABLED', true),
        'provider' => env('AI_LEKTORAT_PROVIDER'),
        'model' => env('AI_LEKTORAT_MODEL', env('OPENAI_MODEL')),
        'reasoning_effort' => env('AI_LEKTORAT_REASONING_EFFORT', env('OPENAI_REASONING_EFFORT', 'medium')),
        'language' => env('AI_LEKTORAT_LANGUAGE', 'de'),
    ],

];
