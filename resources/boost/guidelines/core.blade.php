## CBU Currency

This package provides Central Bank of Uzbekistan (CBU) exchange rates for Laravel: daily rates, currency conversion (via UZS), database syncing, and ready-made API endpoints.

### Rules

- Access everything through the `Cbu\Currency\Facades\CbuCurrency` facade and its fluent builders: `rates()`, `convert()`, `currencies()`, `sync()`.
- All rates are quoted against UZS. Dates use `Y-m-d` format. Rate/convert calls may throw `Cbu\Currency\Exceptions\CbuApiException`.
- Prefer the `Cbu\Currency\Enums\CurrencyCcy` enum over raw currency-code strings.
- For detailed usage (builders, artisan sync commands, API endpoints, config), activate the `cbu-currency-development` skill.

@verbatim
<code-snippet name="Get today's USD rate" lang="php">
$usd = CbuCurrency::rates()->ccy('USD')->get(); // CurrencyRateDto
</code-snippet>

<code-snippet name="Convert 100 USD to UZS" lang="php">
$result = CbuCurrency::convert()->amount(100)->from('USD')->to('UZS')->get(); // ConversionResultDto
</code-snippet>
@endverbatim
