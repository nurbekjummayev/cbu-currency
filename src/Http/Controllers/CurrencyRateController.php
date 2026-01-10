<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Controllers;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Cbu\Currency\Http\Requests\GetRateByCcyRequest;
use Cbu\Currency\Http\Requests\GetRatesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Currency Rate Controller
 *
 * Handles currency rate endpoints including fetching rates by date,
 * fetching specific currency rates, and getting today's rates.
 */
class CurrencyRateController extends Controller
{
    /**
     * Get all currency rates for a specific date
     *
     * Returns all currency exchange rates for the specified date.
     * If no date provided, returns today's rates.
     *
     * @param GetRatesRequest $request
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/rates?date=2025-01-15
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "ccy": "USD",
     *       "rate": "12500.00",
     *       "diff": "50.00",
     *       "date": "2025-01-15",
     *       "currency": {
     *         "ccy": "USD",
     *         "name_en": "US Dollar"
     *       }
     *     }
     *   ]
     * }
     *
     * Error Response (500):
     * {
     *   "msg": "Failed to fetch currency rates",
     *   "error": "Detailed error message",
     *   "success": false,
     *   "data": []
     * }
     */
    public function index(GetRatesRequest $request): JsonResponse
    {
        try {
            $date = $request->getDate();

            $rates = CbuCurrency::rates()
                ->date($date)
                ->all();

            return okResponse(data: $rates);
        } catch (CbuApiException $e) {
            return serverErrorResponse(
                msg: 'Failed to fetch currency rates',
                errorMsg: $e->getMessage()
            );
        }
    }

    /**
     * Get specific currency rate by code
     *
     * Returns the exchange rate for a specific currency on a given date.
     * If no date provided, returns today's rate.
     *
     * @param GetRateByCcyRequest $request
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/rates/USD?date=2025-01-15
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "ccy": "USD",
     *     "rate": "12500.00",
     *     "diff": "50.00",
     *     "nominal": 1,
     *     "date": "2025-01-15",
     *     "currency": {
     *       "ccy": "USD",
     *       "code": "840",
     *       "name_uz": "AQSH dollari",
     *       "name_oz": "АҚШ доллари",
     *       "name_ru": "Доллар США",
     *       "name_en": "US Dollar"
     *     }
     *   }
     * }
     *
     * Error Response (500):
     * {
     *   "msg": "Failed to fetch rate for currency USD",
     *   "error": "Detailed error message",
     *   "success": false,
     *   "data": []
     * }
     */
    public function show(GetRateByCcyRequest $request): JsonResponse
    {
        try {
            $date = $request->getDate();
            $ccy = $request->getCcy();

            $rate = CbuCurrency::rates()
                ->date($date)
                ->ccy($ccy)
                ->get();

            return okResponse(data: $rate);
        } catch (CbuApiException $e) {
            return serverErrorResponse(
                msg: "Failed to fetch rate for currency {$request->getCcy()}",
                errorMsg: $e->getMessage()
            );
        } catch (\Exception $e) {
            return serverErrorResponse(
                msg: 'An error occurred while fetching currency rate',
                errorMsg: $e->getMessage()
            );
        }
    }

    /**
     * Get today's rates for all currencies
     *
     * Convenience endpoint to fetch today's rates without date parameter.
     *
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/rates/today
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": [...]
     * }
     *
     * Error Response (500):
     * {
     *   "msg": "Failed to fetch today's currency rates",
     *   "error": "Detailed error message",
     *   "success": false,
     *   "data": []
     * }
     */
    public function today(): JsonResponse
    {
        try {
            $today = now()->format('Y-m-d');

            $rates = CbuCurrency::rates()
                ->date($today)
                ->all();

            return okResponse(data: $rates);
        } catch (CbuApiException $e) {
            return serverErrorResponse(
                msg: 'Failed to fetch today\'s currency rates',
                errorMsg: $e->getMessage()
            );
        }
    }
}
