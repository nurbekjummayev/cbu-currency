<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Return the plain list of supported ISO 4217 currency codes.
 */
class ListCurrencyCodesTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Return the plain list of ISO 4217 currency codes supported by the '
            .'Central Bank of Uzbekistan (CBU), for example ["USD", "EUR", "RUB", ...]. '
            .'Useful for validating a code before calling the rate or conversion tools.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [], function () {
            $codes = $this->currencyCodes();

            $summary = sprintf('There are %d supported currency codes.', count($codes));

            return [$summary, [
                'count' => count($codes),
                'codes' => $codes,
            ]];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
