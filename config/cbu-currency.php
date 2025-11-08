<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CBU API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for Central Bank of Uzbekistan API
    |
    */
    'base_url' => env('CBU_BASE_URL', 'https://cbu.uz/ru/arkhiv-kursov-valyut/json'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration
    |--------------------------------------------------------------------------
    |
    | How long to cache the currency rates (in minutes)
    |
    */
    'cache_duration' => env('CBU_CACHE_DURATION', null),

    /*
    |--------------------------------------------------------------------------
    | Calculation Scale
    |--------------------------------------------------------------------------
    |
    | The number of decimal places for BCMath calculations
    | Recommended: 2 for currency conversions to maintain precision
    |
    */
    'scale' => env('CBU_SCALE', 2),

    /*
    |--------------------------------------------------------------------------
    | Data Source
    |--------------------------------------------------------------------------
    |
    | Determines where to fetch currency rates from:
    | - 'database': Fetch rates from local database (default, faster)
    | - 'api': Fetch rates directly from CBU API (live data, slower)
    |
    */
    'source' => env('CBU_SOURCE', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Enable or disable package logging for debugging and monitoring.
    | Set to false in production to reduce log volume.
    |
    */
    'log_enabled' => env('CBU_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API routes for the currency package.
    | - prefix: The URL prefix for all currency API routes (e.g., 'api/currency')
    | - middleware: Array of middleware to apply to routes
    |
    */
    'routes' => [
        'prefix' => env('CBU_ROUTE_PREFIX', 'api/currency'),
        'middleware' => ['api'],
    ],
];
