<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * Get the details (codes and names) of a single currency by its code.
 */
class GetCurrencyTool extends AbstractCurrencyTool
{
    public function description(): Stringable|string
    {
        return 'Get the details of a single currency by its ISO 4217 code, including the '
            .'numeric code and the name in Uzbek (Latin and Cyrillic), Russian and '
            .'English. This returns currency metadata only — use the rate tools to get '
            .'exchange rates.';
    }

    public function handle(Request $request): Stringable|string
    {
        return $this->run($request, [
            'ccy' => ['required', 'string', Rule::in($this->currencyCodes())],
        ], function (array $data) {
            $ccy = strtoupper($data['ccy']);

            $currency = CbuCurrency::currencies()->ccy($ccy)->get();

            if ($currency === null) {
                return ['No currency found for code '.$ccy.'.', ['ccy' => $ccy, 'found' => false]];
            }

            $summary = sprintf('%s — %s.', $ccy, $currency->name_en);

            return [$summary, $currency->toArray()];
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ccy' => $schema->string()
                ->enum($this->currencyCodes())
                ->description('ISO 4217 currency code (e.g. USD, EUR, RUB).')
                ->required(),
        ];
    }
}
