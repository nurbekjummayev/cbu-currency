<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Get the conversion rate for 1 unit of one currency into another.
 */
class GetConversionRateTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Get the exchange rate between two currencies: how much of the target '
            .'currency you get for 1 unit of the source currency, using official CBU '
            .'rates. This is a convenience over the convert tool with amount = 1. The '
            .'response shows the from_rate and to_rate (both quoted in UZS) and the '
            .'resulting rate. Defaults to today; a past date can be supplied.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [
            'from' => ['required', 'string', Rule::in($this->currencyCodes())],
            'to' => ['required', 'string', Rule::in($this->currencyCodes())],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'scale' => ['nullable', 'integer', 'min:0', 'max:20'],
        ], function (array $data) {
            $date = $data['date'] ?? now()->format('Y-m-d');

            $result = CbuCurrency::convert()
                ->amount(1)
                ->from(strtoupper($data['from']))
                ->to(strtoupper($data['to']))
                ->date($date)
                ->get();

            if (isset($data['scale'])) {
                $result = $result->round((int) $data['scale']);
            }

            $payload = $result->toArray();

            $summary = sprintf(
                '1 %s = %s %s on %s (1 %s = %s UZS, 1 %s = %s UZS).',
                $payload['from_currency'],
                rtrim(rtrim(sprintf('%.6f', $payload['result']), '0'), '.'),
                $payload['to_currency'],
                $payload['date'],
                $payload['from_currency'],
                $payload['from_rate'],
                $payload['to_currency'],
                $payload['to_rate'],
            );

            return [$summary, $payload];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('Source ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'to' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('Target ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'date' => $schema->string()
                ->description('Rate date in Y-m-d format. Defaults to today. Cannot be in the future.'),
            'scale' => $schema->integer()
                ->min(0)
                ->max(20)
                ->description('Optional number of decimals (0-20) to round the rate to (half-up). Omit for full precision.'),
        ];
    }
}
