<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Get all CBU exchange rates for a given date (defaults to today).
 */
class GetRatesTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Get the official Central Bank of Uzbekistan (CBU) exchange rates for '
            .'every currency on a specific date. Each entry is quoted against UZS and '
            .'includes the rate, day-over-day change (diff), nominal and currency names. '
            .'If no date is provided, today\'s rates are returned.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], function (array $data) {
            $date = $data['date'] ?? now()->format('Y-m-d');

            $rates = CbuCurrency::rates()
                ->date($date)
                ->all();

            $items = $rates->map(fn (CurrencyRateDto $dto) => $dto->toArray())->values()->all();

            $summary = sprintf(
                'Retrieved %d currency rate(s) against UZS for %s.',
                count($items),
                $date,
            );

            return [$summary, [
                'date' => $date,
                'count' => count($items),
                'rates' => $items,
            ]];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('Date in Y-m-d format (e.g. 2026-01-15). Defaults to today. Cannot be in the future.'),
        ];
    }
}
