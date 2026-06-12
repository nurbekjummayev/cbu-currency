<?php

declare(strict_types=1);

namespace Cbu\Currency\Mcp\Tools;

use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get all Central Bank of Uzbekistan (CBU) exchange rates for a date. Every rate is quoted against UZS. Returns the rate, daily diff, nominal, and multilingual currency names for every available currency.')]
class GetRatesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $date = $validated['date'] ?? now()->format('Y-m-d');

        try {
            $rates = CbuCurrency::rates()->date($date)->all();
        } catch (CbuApiException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'date' => $date,
            'base_currency' => 'UZS',
            'rates' => $rates->map(fn (CurrencyRateDto $rate) => $rate->toArray())->values(),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('Date in Y-m-d format (e.g. 2026-01-15). Defaults to today. Cannot be in the future.'),
        ];
    }
}
