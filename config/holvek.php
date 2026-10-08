<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | General Routing & Middleware
    |--------------------------------------------------------------------------
    */
    'path' => env('HOLVEK_PATH', 'holvek'),
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Global SEO Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'og_image' => env('HOLVEK_DEFAULT_OG_IMAGE', '/images/default-og.png'),
    ],

    'social' => [
        'twitter' => env('HOLVEK_TWITTER_HANDLE', '@holvek'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys & Cloud Settings
    |--------------------------------------------------------------------------
    | These keys are required for integration with specific external providers.
    */
    'api' => [
        'cloud_key' => env('HOLVEK_CLOUD_KEY'),
        'webhook_secret' => env('HOLVEK_WEBHOOK_SECRET'),
        'google_pagespeed_key' => env('HOLVEK_PAGESPEED_KEY'),
        'serpapi_key' => env('HOLVEK_SERPAPI_KEY'),
        'indexnow_key' => env('HOLVEK_INDEXNOW_KEY'),
        'google_oauth_token' => env('HOLVEK_GOOGLE_OAUTH_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configurations (GA4)
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'ga4_property_id' => env('HOLVEK_GA4_PROPERTY_ID'),
        'ga4_access_token' => env('HOLVEK_GA4_ACCESS_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generative AI Settings
    |--------------------------------------------------------------------------
    | The provider used for AI content features. Supports 'ollama', 'openai', 'anthropic'.
    */
    'ai' => [
        'provider' => env('HOLVEK_AI_PROVIDER', 'ollama'),
        'ollama_url' => env('HOLVEK_OLLAMA_URL', 'http://localhost:11434/api/generate'),
        'ollama_model' => env('HOLVEK_OLLAMA_MODEL', 'llama3'),
        'openai_key' => env('HOLVEK_OPENAI_KEY'),
        'anthropic_key' => env('HOLVEK_ANTHROPIC_KEY'),
    ],
];
