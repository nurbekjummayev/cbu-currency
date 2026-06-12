<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Controllers;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Cbu\Currency\Http\Requests\ConvertCurrencyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Currency Conversion Controller
 *
 * Handles currency conversion endpoints.
 */
class CurrencyConversionController extends Controller
{
    /**
     * Convert amount between currencies
     *
     * Converts a specified amount from one currency to another
     * using exchange rates for the given date.
     *
     *
     * @example
     * POST /api/currency/convert
     * {
     *   "amount": 100,
     *   "from": "USD",
     *   "to": "UZS",
     *   "date": "2025-01-15"
     * }
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": {
     *     "amount": 100,
     *     "from_currency": "USD",
     *     "to_currency": "UZS",
     *     "result": 1270500,
     *     "from_rate": 12705,
     *     "to_rate": 1,
     *     "amount_in_uzs": 1270500,
     *     "date": "2025-01-15"
     *   }
     * }
     *
     * Error Response (500):
     * {
     *   "msg": "Currency conversion failed",
     *   "error": "Detailed error message",
     *   "success": false,
     *   "data": []
     * }
     */
    public function convert(ConvertCurrencyRequest $request): JsonResponse
    {
        try {
            $amount = $request->getAmount();
            $from = $request->getFrom();
            $to = $request->getTo();
            $date = $request->getDate();
            $scale = $request->getScale();

            $result = CbuCurrency::convert()
                ->amount($amount)
                ->from($from)
                ->to($to)
                ->date($date)
                ->get();

            // Round the final result only when the client asked for it
            if ($scale !== null) {
                $result = $result->round($scale);
            }

            return okResponse(data: $result->toArray());
        } catch (CbuApiException $e) {
            return serverErrorResponse(
                msg: 'Currency conversion failed',
                errorMsg: $e->getMessage()
            );
        } catch (\Exception $e) {
            return serverErrorResponse(
                msg: 'An error occurred during currency conversion',
                errorMsg: $e->getMessage()
            );
        }
    }

    /**
     * Get conversion rate between two currencies
     *
     * Returns the conversion result for 1 unit of source currency
     * to target currency. Uses the same conversion logic as convert().
     *
     * @param  string  $from  Source currency code
     * @param  string  $to  Target currency code
     *
     * @example
     * GET /api/currency/convert/rate/USD/UZS?date=2025-01-15
     *
     * Success Response:
     * {
     *   "msg": null,
     *   "error": null,
     *   "success": true,
     *   "data": {
     *     "amount": 1,
     *     "from_currency": "USD",
     *     "to_currency": "UZS",
     *     "result": 12705,
     *     "from_rate": 12705,
     *     "to_rate": 1,
     *     "amount_in_uzs": 12705,
     *     "date": "2025-01-15"
     *   }
     * }
     *
     * Error Response (500):
     * {
     *   "msg": "Failed to get conversion rate",
     *   "error": "Detailed error message",
     *   "success": false,
     *   "data": []
     * }
     */
    public function rate(string $from, string $to): JsonResponse
    {
        // Validate outside the try block so validation errors return 422,
        // not a generic 500 from the broad exception handler below.
        $validated = request()->validate([
            'date' => ['nullable', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'scale' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $from = strtoupper($from);
            $to = strtoupper($to);
            $date = $validated['date'] ?? now()->format('Y-m-d');
            $scale = $validated['scale'] ?? null;

            $result = CbuCurrency::convert()
                ->amount(1)
                ->from($from)
                ->to($to)
                ->date($date)
                ->get();

            // Round the final result only when the client asked for it
            if ($scale !== null) {
                $result = $result->round((int) $scale);
            }

            return okResponse(data: $result->toArray());
        } catch (CbuApiException $e) {
            return serverErrorResponse(
                msg: 'Failed to get conversion rate',
                errorMsg: $e->getMessage()
            );
        } catch (\Exception $e) {
            return serverErrorResponse(
                msg: 'An error occurred while fetching conversion rate',
                errorMsg: $e->getMessage()
            );
        }
    }
}
