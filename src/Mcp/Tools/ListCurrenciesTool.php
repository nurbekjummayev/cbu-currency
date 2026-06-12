<?php

declare(strict_types=1);

namespace Cbu\Currency\Mcp\Tools;

use Cbu\Currency\DTOs\CurrencyDto;
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

#[Description('List the currencies known to the Central Bank of Uzbekistan with their ISO codes and names in Uzbek (latin and cyrillic), Russian, and English. Pass a currency code to get a single currency.')]
class ListCurrenciesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'ccy' => ['nullable', 'string', Rule::in(array_column(CurrencyCcy::cases(), 'value'))],
        ]);

        try {
            if (isset($validated['ccy'])) {
                $currency = CbuCurrency::currencies()->ccy(strtoupper($validated['ccy']))->get();

                if (! $currency) {
                    return Response::error("Currency {$validated['ccy']} not found");
                }

                return Response::json($currency->toArray());
            }

            $currencies = CbuCurrency::currencies()->all();
        } catch (CbuApiException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'currencies' => $currencies->map(fn (CurrencyDto $currency) => $currency->toArray())->values(),
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
            'ccy' => $schema->string()
                ->enum(array_column(CurrencyCcy::cases(), 'value'))
                ->description('Optional ISO 4217 currency code to fetch a single currency. Omit to list all currencies.'),
        ];
    }
}
