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

#[Description('Convert an amount between currencies using official CBU rates. UZS is the base: foreign-to-foreign conversions go through UZS. Returns the full-precision result unless a scale is given, in which case the final result is rounded half-up to that many decimals.')]
class ConvertCurrencyTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $currencyCodes = array_column(CurrencyCcy::cases(), 'value');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'from' => ['required', 'string', Rule::in($currencyCodes)],
            'to' => ['required', 'string', Rule::in($currencyCodes)],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'scale' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $result = CbuCurrency::convert()
                ->amount((float) $validated['amount'])
                ->from(strtoupper($validated['from']))
                ->to(strtoupper($validated['to']))
                ->date($validated['date'] ?? now()->format('Y-m-d'))
                ->get();
        } catch (CbuApiException $e) {
            return Response::error($e->getMessage());
        }

        if (isset($validated['scale'])) {
            $result = $result->round((int) $validated['scale']);
        }

        return Response::json($result->toArray());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $currencyCodes = array_column(CurrencyCcy::cases(), 'value');

        return [
            'amount' => $schema->number()
                ->description('The amount to convert. Must be greater than 0.')
                ->required(),
            'from' => $schema->string()
                ->enum($currencyCodes)
                ->description('Source ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'to' => $schema->string()
                ->enum($currencyCodes)
                ->description('Target ISO 4217 currency code (e.g. USD, EUR, UZS).')
                ->required(),
            'date' => $schema->string()
                ->description('Conversion date in Y-m-d format. Defaults to today. Cannot be in the future.'),
            'scale' => $schema->integer()
                ->description('Optional number of decimals (0-20) to round the final result to (half-up). Omit for full precision.'),
        ];
    }
}
