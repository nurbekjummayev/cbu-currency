<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Controllers;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use ValueError;

/**
 * Currency Controller
 *
 * Handles currency-related endpoints including listing all currencies
 * and retrieving specific currency information.
 */
class CurrencyController extends Controller
{
    /**
     * Get all available currencies
     *
     * Returns a list of all currencies from database.
     * Includes currency codes, names in multiple languages.
     *
     *
     * @example
     * GET /api/currency/currencies
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "cbu_id": "840",
     *       "code": "840",
     *       "ccy": "USD",
     *       "name_uz": "AQSH dollari",
     *       "name_oz": "АҚШ доллари",
     *       "name_ru": "Доллар США",
     *       "name_en": "US Dollar"
     *     }
     *   ]
     * }
     */
    public function index(): JsonResponse
    {
        $currencies = CbuCurrency::currencies()->all();

        return okResponse(
            data: $currencies->map(fn ($currency) => $currency->toArray())
        );
    }

    /**
     * Get all available currency codes
     *
     * Returns a simple list of all available currency codes from the enum.
     * Useful for validation or dropdowns.
     *
     *
     * @example
     * GET /api/currency/codes
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": ["USD", "EUR", "RUB", "GBP", ...]
     * }
     */
    public function codes(): JsonResponse
    {
        $codes = array_column(CurrencyCcy::cases(), 'value');

        return okResponse(data: $codes);
    }

    /**
     * Get specific currency information
     *
     * Returns detailed information about a specific currency by its code.
     *
     *
     * @example
     * GET /api/currency/currencies/USD
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "cbu_id": "840",
     *     "code": "840",
     *     "ccy": "USD",
     *     "name_uz": "AQSH dollari",
     *     "name_oz": "АҚШ доллари",
     *     "name_ru": "Доллар США",
     *     "name_en": "US Dollar"
     *   }
     * }
     *
     * Error Response (404):
     * {
     *   "msg": "Invalid currency code",
     *   "error": null,
     *   "success": false,
     *   "data": {
     *     "ccy": "XYZ"
     *   }
     * }
     */
    public function show(string $ccy): JsonResponse
    {
        try {
            $ccy = CurrencyCcy::from(strtoupper($ccy));
        } catch (ValueError $e) {
            return notFoundRequestResponse(
                msg: 'Invalid currency code',
                data: ['ccy' => $ccy]
            );
        }

        $currency = CbuCurrency::currencies()
            ->ccy($ccy)
            ->get();

        if (! $currency) {
            return notFoundRequestResponse(
                msg: 'Currency not found',
                data: ['ccy' => $ccy->value]
            );
        }

        return okResponse(data: $currency->toArray());
    }
}
