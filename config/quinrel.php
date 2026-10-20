<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | UI & Dashboard Settings
    |--------------------------------------------------------------------------
    | Control how the Quinrel dashboard is presented in the host application.
    | Supported drivers: 'filament', 'standalone', 'headless', 'none'
    */
    'ui' => [
        'driver' => env('QUINREL_UI_DRIVER', 'standalone'),
        'path' => env('QUINREL_PATH', 'quinrel'),
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global SEO Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'og_image' => env('QUINREL_DEFAULT_OG_IMAGE', '/images/default-og.png'),
        'noindex_non_production' => env('QUINREL_NOINDEX_NON_PRODUCTION', true),
    ],

    'cache' => [
        'ttl' => (int) env('QUINREL_CACHE_TTL', 3600),
    ],

    'rum' => [
        'sample_rate' => (float) env('QUINREL_RUM_SAMPLE_RATE', 1.0),
        'retention_days' => (int) env('QUINREL_RUM_RETENTION_DAYS', 30),
    ],

    'llms_txt' => [
        'enabled' => env('QUINREL_LLMS_TXT_ENABLED', true),
    ],

    'social' => [
        'twitter' => env('QUINREL_TWITTER_HANDLE', '@quinrel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys & Cloud Settings
    |--------------------------------------------------------------------------
    | These keys are required for integration with specific external providers.
    */
    'api' => [
        'cloud_key' => env('QUINREL_CLOUD_KEY'),
        'webhook_secret' => env('QUINREL_WEBHOOK_SECRET'),
        'google_pagespeed_key' => env('QUINREL_PAGESPEED_KEY'),
        'serpapi_key' => env('QUINREL_SERPAPI_KEY'),
        'google_oauth_token' => env('QUINREL_GOOGLE_OAUTH_TOKEN'),
        'gsc_service_account' => env('QUINREL_GSC_SERVICE_ACCOUNT'), // path to json or json string
        'indexnow_key' => env('QUINREL_INDEXNOW_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Search Console Settings
    |--------------------------------------------------------------------------
    */
    'gsc' => [
        'site_url' => env('QUINREL_GSC_SITE_URL', env('APP_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Indexing API Settings
    |--------------------------------------------------------------------------
    */
    'google_indexing' => [
        'enabled' => env('QUINREL_GOOGLE_INDEXING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow Settings
    |--------------------------------------------------------------------------
    */
    'indexnow' => [
        'enabled' => env('QUINREL_INDEXNOW_ENABLED', false),
        'key' => env('QUINREL_INDEXNOW_KEY'),
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
        'ga4_property_id' => env('QUINREL_GA4_PROPERTY_ID'),
        'ga4_access_token' => env('QUINREL_GA4_ACCESS_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generative AI Settings
    |--------------------------------------------------------------------------
    | The provider used for AI content features. Supports 'ollama', 'openai', 'anthropic'.
    */
    'ai' => [
        'provider' => env('QUINREL_AI_PROVIDER', 'ollama'),
        'ollama_url' => env('QUINREL_OLLAMA_URL', 'http://localhost:11434/api/generate'),
        'ollama_model' => env('QUINREL_OLLAMA_MODEL', 'llama3'),
        'openai_key' => env('QUINREL_OPENAI_KEY'),
        'anthropic_key' => env('QUINREL_ANTHROPIC_KEY'),
    ],
];
