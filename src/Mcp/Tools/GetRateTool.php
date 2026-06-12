<?php

declare(strict_types=1);

namespace Cbu\Currency\Mcp\Tools;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get the Central Bank of Uzbekistan (CBU) exchange rate for a single currency on a date. The rate is quoted against UZS for the given nominal (number of units).')]
class GetRateTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'ccy' => ['required', 'string', Rule::in(array_column(CurrencyCcy::cases(), 'value'))],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $date = $validated['date'] ?? now()->format('Y-m-d');

        try {
            $rate = CbuCurrency::rates()
                ->date($date)
                ->ccy(strtoupper($validated['ccy']))
                ->get();
        } catch (CbuApiException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json($rate->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ccy' => $schema->string()
                ->enum(array_column(CurrencyCcy::cases(), 'value'))
                ->description('ISO 4217 currency code (e.g. USD, EUR, RUB).')
                ->required(),
            'date' => $schema->string()
                ->description('Date in Y-m-d format (e.g. 2026-01-15). Defaults to today. Cannot be in the future.'),
        ];
    }
}
