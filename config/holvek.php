<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | UI & Dashboard Settings
    |--------------------------------------------------------------------------
    | Control how the Holvek dashboard is presented in the host application.
    | Supported drivers: 'filament', 'standalone', 'headless', 'none'
    */
    'ui' => [
        'driver' => env('HOLVEK_UI_DRIVER', 'standalone'),
        'path' => env('HOLVEK_PATH', 'holvek'),
        'middleware' => ['web'],
    ],

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
        'google_oauth_token' => env('HOLVEK_GOOGLE_OAUTH_TOKEN'),
        'gsc_service_account' => env('HOLVEK_GSC_SERVICE_ACCOUNT'), // path to json or json string
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Search Console Settings
    |--------------------------------------------------------------------------
    */
    'gsc' => [
        'site_url' => env('HOLVEK_GSC_SITE_URL', env('APP_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Indexing API Settings
    |--------------------------------------------------------------------------
    */
    'google_indexing' => [
        'enabled' => env('HOLVEK_GOOGLE_INDEXING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow Settings
    |--------------------------------------------------------------------------
    */
    'indexnow' => [
        'enabled' => env('HOLVEK_INDEXNOW_ENABLED', false),
        'key' => env('HOLVEK_INDEXNOW_KEY'),
        'search_engines' => [
            'api.indexnow.org',
            'www.bing.com',
            'yandex.com',
        ],
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
