<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\DTOs\CurrencyDto;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * List every currency known to the package with its codes and names.
 */
class ListCurrenciesTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'List all currencies supported by the Central Bank of Uzbekistan (CBU), '
            .'ordered by currency code. Each entry includes the ISO 4217 code (ccy), the '
            .'numeric code and the currency name in Uzbek (Latin and Cyrillic), Russian '
            .'and English. This does not include exchange rates — use the rate tools for '
            .'that.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [], function () {
            $currencies = CbuCurrency::currencies()->all();

            $items = $currencies->map(fn (CurrencyDto $dto) => $dto->toArray())->values()->all();

            $summary = sprintf('Listed %d supported currencies.', count($items));

            return [$summary, [
                'count' => count($items),
                'currencies' => $items,
            ]];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
