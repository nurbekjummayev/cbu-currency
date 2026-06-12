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
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for a CBU API response
    |
    */
    'timeout' => env('CBU_TIMEOUT', 60),

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
    | Result Scale
    |--------------------------------------------------------------------------
    |
    | The number of decimal places for the FINAL conversion result.
    | Default 0 means NO rounding — the result is returned at full computed
    | precision. Internal calculations always run at full precision and are
    | never rounded mid-calculation. Set a positive value (or use ->scale(n)
    | per call) to round the final result half-up, or round the returned
    | result yourself with ->round(n).
    |
    */
    'scale' => env('CBU_SCALE', 0),

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
    | - enabled: Enable or disable package routes entirely
    | - prefix: The URL prefix for all currency API routes (e.g., 'api/currency')
    | - middleware: Array of middleware to apply to routes
    |
    */
    'routes' => [
        'enabled' => env('CBU_ROUTES_ENABLED', true),
        'prefix' => env('CBU_ROUTES_PREFIX', 'api/currency'),
        'middleware' => ['api'],
    ],
];
