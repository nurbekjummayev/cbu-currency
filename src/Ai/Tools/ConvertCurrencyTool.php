<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Convert an amount between two currencies using official CBU rates.
 */
class ConvertCurrencyTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Convert an amount from one currency to another using the official '
            .'Central Bank of Uzbekistan (CBU) exchange rates. UZS (Uzbekistani so\'m) '
            .'is the base currency: foreign-to-foreign conversions are calculated through '
            .'UZS. The response includes the full breakdown — the source amount, the '
            .'per-unit from_rate and to_rate used, the intermediate amount in UZS, the '
            .'final result and the rate date. Returns full precision unless a scale is '
            .'given, in which case the final result is rounded half-up.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [
            'amount' => ['required', 'numeric', 'gt:0'],
            'from' => ['required', 'string', Rule::in($this->currencyCodes())],
            'to' => ['required', 'string', Rule::in($this->currencyCodes())],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'scale' => ['nullable', 'integer', 'min:0', 'max:20'],
        ], function (array $data) {
            $date = $data['date'] ?? now()->format('Y-m-d');

            $result = CbuCurrency::convert()
                ->amount((float) $data['amount'])
                ->from(strtoupper($data['from']))
                ->to(strtoupper($data['to']))
                ->date($date)
                ->get();

            if (isset($data['scale'])) {
                $result = $result->round((int) $data['scale']);
            }

            $payload = $result->toArray();

            $summary = sprintf(
                '%s %s = %s %s on %s (via UZS base; 1 %s = %s UZS, 1 %s = %s UZS).',
                rtrim(rtrim(sprintf('%.4f', $payload['amount']), '0'), '.'),
                $payload['from_currency'],
                rtrim(rtrim(sprintf('%.4f', $payload['result']), '0'), '.'),
                $payload['to_currency'],
                $payload['date'],
                $payload['from_currency'],
                $payload['from_rate'],
                $payload['to_currency'],
                $payload['to_rate'],
            );

            $payload['explanation'] = sprintf(
                'from_rate is the UZS value of 1 %s and to_rate is the UZS value of 1 %s. '
                .'The amount is first converted to UZS (amount_in_uzs), then divided by '
                .'to_rate to reach the target currency. UZS itself always has a rate of 1.',
                $payload['from_currency'],
                $payload['to_currency'],
            );

            return [$summary, $payload];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()
                ->description('The amount to convert. Must be greater than 0.')
                ->required(),
            'from' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('Source ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'to' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('Target ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'date' => $schema->string()
                ->description('Conversion date in Y-m-d format. Defaults to today. Cannot be in the future.'),
            'scale' => $schema->integer()
                ->min(0)
                ->max(20)
                ->description('Optional number of decimals (0-20) to round the final result to (half-up). Omit for full precision.'),
        ];
    }
}
