<?php

declare(strict_types=1);

use Cbu\Currency\Http\Controllers\CurrencyController;
use Cbu\Currency\Http\Controllers\CurrencyConversionController;
use Cbu\Currency\Http\Controllers\CurrencyRateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CBU Currency API Routes
|--------------------------------------------------------------------------
|
| These routes provide RESTful API endpoints for currency operations.
| Routes are prefixed and middleware configured via config/cbu-currency.php
|
| Configuration:
| - Prefix: config('cbu-currency.routes.prefix')
| - Middleware: config('cbu-currency.routes.middleware')
|
*/

// Currency information endpoints
Route::prefix('currencies')->group(function () {
    // Get all currencies
    Route::get('/', [CurrencyController::class, 'index'])->name('cbu.currencies.index');

    // Get all currency codes
    Route::get('/codes', [CurrencyController::class, 'codes'])->name('cbu.currencies.codes');

    // Get specific currency by code
    Route::get('/{ccy}', [CurrencyController::class, 'show'])->name('cbu.currencies.show');
});

// Currency rates endpoints
Route::prefix('rates')->group(function () {
    // Get today's rates
    Route::get('/today', [CurrencyRateController::class, 'today'])->name('cbu.rates.today');

    // Get all rates for a specific date
    Route::get('/', [CurrencyRateController::class, 'index'])->name('cbu.rates.index');

    // Get rate for specific currency
    Route::get('/{ccy}', [CurrencyRateController::class, 'show'])->name('cbu.rates.show')
        ->where('ccy', '[A-Z]{3}');
});

// Currency conversion endpoints
Route::prefix('convert')->group(function () {
    // Convert currency
    Route::post('/', [CurrencyConversionController::class, 'convert'])->name('cbu.convert');

    // Get conversion rate between currencies
    Route::get('/rate/{from}/{to}', [CurrencyConversionController::class, 'rate'])
        ->name('cbu.convert.rate')
        ->where(['from' => '[A-Z]{3}', 'to' => '[A-Z]{3}']);
});
