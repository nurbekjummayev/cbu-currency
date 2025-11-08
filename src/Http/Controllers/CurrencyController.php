<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Controllers;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

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
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/currencies
     *
     * Success Response:
     * {
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

        return response()->json([
            'success' => true,
            'data' => $currencies->map(fn($currency) => $currency->toArray()),
        ]);
    }

    /**
     * Get all available currency codes
     *
     * Returns a simple list of all available currency codes from the enum.
     * Useful for validation or dropdowns.
     *
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/codes
     *
     * Response:
     * {
     *   "success": true,
     *   "data": ["USD", "EUR", "RUB", "GBP", ...],
     * }
     */
    public function codes(): JsonResponse
    {
        $codes = array_column(CurrencyCcy::cases(), 'value');

        return response()->json([
            'success' => true,
            'data' => $codes,
        ]);
    }

    /**
     * Get specific currency information
     *
     * Returns detailed information about a specific currency by its code.
     *
     * @param string $ccy
     * @return JsonResponse
     *
     * @example
     * GET /api/currency/currencies/USD
     *
     * Success Response:
     * {
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
     * Error Response:
     * {
     *   "success": false,
     *   "errorMessage": "Currency not found",
     *   "error": "Currency 'XYZ' not found in database"
     * }
     */
    public function show(string $ccy): JsonResponse
    {
        try {
            $ccy = CurrencyCcy::from(strtoupper($ccy));
        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'errorMessage' => 'Invalid currency code',
                'error' => "Currency code '{$code}' is not supported",
            ], 404);
        }

        $currency = CbuCurrency::currencies()
            ->ccy($ccy)
            ->get();

        if (!$currency) {
            return response()->json([
                'success' => false,
                'errorMessage' => 'Currency not found',
                'error' => "Currency '{$ccy->value}' not found in database",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $currency->toArray(),
        ]);
    }
}
