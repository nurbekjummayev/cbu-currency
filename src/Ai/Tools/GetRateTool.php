<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Get the CBU exchange rate for a single currency on a given date.
 */
class GetRateTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Get the official Central Bank of Uzbekistan (CBU) exchange rate for a '
            .'single currency on a specific date. The rate is quoted against UZS for the '
            .'currency\'s nominal (number of units). The response includes the rate, the '
            .'day-over-day change (diff), the nominal, the numeric code and the currency '
            .'names. Defaults to today if no date is given.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [
            'ccy' => ['required', 'string', Rule::in($this->currencyCodes())],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], function (array $data) {
            $date = $data['date'] ?? now()->format('Y-m-d');

            $rate = CbuCurrency::rates()
                ->date($date)
                ->ccy(strtoupper($data['ccy']))
                ->get();

            $payload = $rate->toArray();

            $summary = sprintf(
                '%d %s = %s UZS on %s (change vs previous: %s).',
                $payload['nominal'],
                $payload['ccy']->value,
                $payload['rate'],
                $payload['date'],
                $payload['diff'],
            );

            return [$summary, $payload];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ccy' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('ISO 4217 currency code (e.g. USD, EUR, RUB).')
                ->required(),
            'date' => $schema->string()
                ->description('Date in Y-m-d format (e.g. 2026-01-15). Defaults to today. Cannot be in the future.'),
        ];
    }
}
