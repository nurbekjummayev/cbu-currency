---
name: cbu-currency-development
description: Work with Central Bank of Uzbekistan (CBU) exchange rates using the nurbekjummayev/cbu-currency package — fetch rates, convert currencies, sync rates to the database, and expose currency API endpoints.
---

# CBU Currency Development

## When to use this skill

Use this skill when the application needs Central Bank of Uzbekistan (CBU) exchange rates: fetching daily rates, converting amounts between currencies (UZS and foreign currencies), syncing rates into the local database, or working with the package's built-in API endpoints.

## Core concepts

- All rates are quoted **against UZS** (Uzbekistani so'm). Conversions between two foreign currencies go through UZS as the intermediate (cross conversion).
- CBU quotes some currencies **per nominal** (e.g. IDR, IRR, VND are quoted per 10 units). The package handles this automatically — conversions always use the per-1-unit rate (`rate / nominal`).
- Precision: internal calculations run at full precision (20 decimal places) and are **never rounded mid-calculation**. By default (scale 0) the result is NOT rounded — it is returned at full computed precision. Round only when needed: per call with `->scale(n)` (half-up), or on the returned DTO with `->round(n)`.
- The package has two data sources, selected via `config('cbu-currency.source')` (`CBU_SOURCE` env):
  - `api` — live data straight from the CBU API (slower, always fresh).
  - `database` — local tables with automatic API fallback: if a rate is missing for the requested date, it is fetched from the API and cached in the database transparently.
- All entry points go through the `Cbu\Currency\Facades\CbuCurrency` facade, which exposes four fluent builders: `rates()`, `convert()`, `currencies()`, and `sync()`.
- Money math uses BCMath with a configurable scale (`CBU_SCALE`, default 2). Do not replace it with plain float arithmetic.

## Fetching rates

```php
use Cbu\Currency\Facades\CbuCurrency;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencySource;

// All rates for today (returns Collection<CurrencyRateDto>)
$rates = CbuCurrency::rates()->all();

// All rates for a specific date (Y-m-d)
$rates = CbuCurrency::rates()->date('2026-01-15')->all();

// Single currency rate (returns CurrencyRateDto)
$usd = CbuCurrency::rates()->ccy('USD')->get();
$usd = CbuCurrency::rates()->ccy(CurrencyCcy::USD)->get();

// By ISO 4217 numeric code (840 = USD)
$usd = CbuCurrency::rates()->code(840)->get();

// Force a specific source for this query only
$live = CbuCurrency::rates()->source(CurrencySource::API)->ccy('EUR')->get();

// Cache the result (minutes; no argument = config default CBU_CACHE_DURATION)
$rates = CbuCurrency::rates()->cache(60)->all();
```

`CurrencyRateDto` public properties: `rate`, `diff`, `nominal`, `date`, `ccy` (CurrencyCcy enum), `code` (CurrencyNumericCode enum), `currency_date`, `name_en`, `name_uz`, `name_oz`, `name_ru`. Call `->toArray()` to serialize.

## Converting currencies

```php
// Foreign -> UZS
$result = CbuCurrency::convert()->amount(100)->from('USD')->to('UZS')->get();

// UZS -> foreign
$result = CbuCurrency::convert()->amount(1_000_000)->from('UZS')->to('EUR')->get();

// Cross conversion (USD -> EUR via UZS)
$result = CbuCurrency::convert()->amount(50)->from('USD')->to('EUR')->get();

// Historical conversion
$result = CbuCurrency::convert()
    ->amount(100)
    ->from('USD')
    ->to('UZS')
    ->date('2026-01-15')
    ->get();

// Round the final result to 2 decimals (half-up); intermediates stay exact
$result = CbuCurrency::convert()->amount(100)->from('RUB')->to('USD')->scale(2)->get();

// Or round the returned DTO afterwards (rates stay untouched)
$result = CbuCurrency::convert()->amount(100)->from('RUB')->to('USD')->get()->round(2);
```

`get()` returns a `ConversionResultDto` with: `amount`, `fromCurrency`, `toCurrency`, `result`, `fromRate`, `toRate`, `amountInUzs`, `date`. It throws `Cbu\Currency\Exceptions\CbuApiException` when required parameters are missing, the amount is not positive, or the CBU API fails — wrap user-facing calls in try/catch.

## Currencies (metadata)

```php
// All currencies with multilingual names (uz, oz, ru, en)
$currencies = CbuCurrency::currencies()->all();

// One currency (returns CurrencyDto or null)
$usd = CbuCurrency::currencies()->ccy('USD')->get();
```

## Syncing rates to the database

Prefer `source=database` in production and sync rates on a schedule instead of hitting the CBU API per request.

```php
// Programmatic sync
CbuCurrency::sync()->save();                       // today
CbuCurrency::sync()->date('2026-01-15')->save();   // specific date
CbuCurrency::sync()->lastDays(7)->save();          // last N days
CbuCurrency::sync()->onlyCurrencies()->save();     // currency metadata only, no rates
```

Artisan commands:

```bash
php artisan cbu:sync-rates                                  # last 7 days (default)
php artisan cbu:sync-rates 30                               # last 30 days
php artisan cbu:sync-rates --date=2026-01-15                # one date
php artisan cbu:sync-rates --from=2026-01-01 --to=2026-01-31 # date range (max 365 days)
php artisan cbu:sync-currencies                             # currency metadata only
```

Recommended scheduler entry (`routes/console.php` or `bootstrap/app.php` schedule):

```php
Schedule::command('cbu:sync-rates 1')->dailyAt('09:30')->timezone('Asia/Tashkent');
```

## Built-in API endpoints

The package registers routes under the configured prefix (`CBU_ROUTES_PREFIX`, default `api/currency`) with the `api` middleware group:

- `GET {prefix}/currencies` — all currencies
- `GET {prefix}/currencies/codes` — list of currency codes
- `GET {prefix}/currencies/{ccy}` — one currency
- `GET {prefix}/rates?date=Y-m-d` — all rates for a date
- `GET {prefix}/rates/today` — today's rates
- `GET {prefix}/rates/{ccy}?date=Y-m-d` — one rate
- `POST {prefix}/convert` — body: `amount`, `from`, `to`, optional `date`, optional `scale` (0–20; when sent, the final result is rounded to that many decimals, otherwise full precision is returned)
- `GET {prefix}/convert/rate/{from}/{to}?date=Y-m-d&scale=N` — rate for 1 unit; optional `scale` works the same way

Disable all package routes with `CBU_ROUTES_ENABLED=false` (or `cbu-currency.routes.enabled` config) when the host app only uses the facade.

## Configuration reference

Publish with `php artisan vendor:publish --tag=cbu-currency-config`. Keys in `config/cbu-currency.php`:

| Key | Env | Default | Purpose |
| --- | --- | --- | --- |
| `base_url` | `CBU_BASE_URL` | CBU JSON API URL | Upstream API |
| `timeout` | `CBU_TIMEOUT` | `60` | HTTP timeout (seconds) |
| `cache_duration` | `CBU_CACHE_DURATION` | `null` | Default cache minutes (null = off) |
| `scale` | `CBU_SCALE` | `0` | Final-result rounding (0 = no rounding, full precision) |
| `source` | `CBU_SOURCE` | `api` | `api` or `database` |
| `log_enabled` | `CBU_LOG_ENABLED` | `true` | Package logging toggle |
| `routes.enabled` | `CBU_ROUTES_ENABLED` | `true` | Register package routes |
| `routes.prefix` | `CBU_ROUTES_PREFIX` | `api/currency` | Route prefix |

Migrations (`currencies`, `currency_rates` tables) load automatically; publish with `--tag=cbu-currency-migrations` if customization is needed.

## Best practices

- Use the `CurrencyCcy` and `CurrencyNumericCode` enums instead of raw strings/ints where possible; invalid codes throw `ValueError`.
- Always pass dates in `Y-m-d` format; invalid formats throw `CbuApiException`.
- In production, set `CBU_SOURCE=database`, schedule `cbu:sync-rates`, and enable `CBU_CACHE_DURATION` to avoid repeated upstream calls.
- Catch `CbuApiException` around conversion/rate calls that depend on the live API.
- Do not bypass the builders by querying `Cbu\Currency\Models\CurrencyRate` directly unless you need custom reporting queries.
