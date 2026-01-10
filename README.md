# CBU Currency - Central Bank of Uzbekistan Exchange Rates

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1%7C%5E8.2%7C%5E8.3%7C%5E8.4-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10%7C%5E11%7C%5E12-red)](https://laravel.com/)
[![Tests](https://img.shields.io/badge/tests-43%20passed-brightgreen)](https://pestphp.com/)

A Laravel package for working with Central Bank of Uzbekistan (CBU) currency exchange rates. This package provides easy-to-use methods for fetching, storing, and converting currencies with high precision using BCMath.

**English version** | [O'zbek versiyasi](README_UZ.md)

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
  - [Currency Conversion](#currency-conversion)
  - [Get Exchange Rates](#get-exchange-rates)
  - [Get Currencies List](#get-currencies-list)
  - [Sync Currencies](#sync-currencies)
- [Artisan Commands](#-artisan-commands)
- [Auto Synchronization](#-auto-synchronization)
- [Database Structure](#-database-structure)
- [Testing](#-testing)
- [API Endpoints](#-api-endpoints)
- [License](#-license)

## ✨ Features

- 📊 **CBU API Integration** - Fetch currency rates from Central Bank API
- 💱 **High-Precision Conversion** - Accurate calculations using BCMath
- 🗄️ **Database Storage** - Store historical rates for fast access
- 🎯 **Simple API** - Intuitive and convenient interface
- ⚙️ **Configurable** - Full configuration capabilities
- 🔄 **Auto Synchronization** - Automatic currency updates
- 📅 **Historical Data** - Rates for any date
- 🚀 **RESTful API** - Complete REST API endpoints
- ✅ **Full Test Coverage** - 43 tests included
- 🎨 **Fluent Interface** - Beautiful and readable code

## 📦 Requirements

- PHP ^8.1|^8.2|^8.3|^8.4
- Laravel ^10.0|^11.0|^12.0
- BCMath PHP Extension
- GuzzleHTTP ^7.0

## 🚀 Installation

### 1. Install via Composer

```bash
composer require nurbekjummayev/cbu-currency
```

### 2. Publish Configuration and Migrations

```bash
# Publish config file
php artisan vendor:publish --tag=cbu-currency-config

# Publish migrations (optional, auto-loaded by default)
php artisan vendor:publish --tag=cbu-currency-migrations
```

## ⚙️ Configuration

Configuration file: `config/cbu-currency.php`

```php
return [
    // CBU API Base URL
    'base_url' => env('CBU_BASE_URL', 'https://cbu.uz/ru/arkhiv-kursov-valyut/json'),

    // Cache duration in minutes
    'cache_duration' => env('CBU_CACHE_DURATION', 60),

    // Default currency code
    'default_currency' => env('CBU_DEFAULT_CURRENCY', 'USD'),

    // BCMath calculation scale (decimal places)
    'scale' => env('CBU_SCALE', 2),

    // Data source: 'database' or 'api'
    'source' => env('CBU_SOURCE', 'database'),

    // Enable/disable logging
    'log_enabled' => env('CBU_LOG_ENABLED', true),

    // API routes configuration
    'routes' => [
        'prefix' => env('CBU_ROUTES_PREFIX', 'api/cbu'),
        'middleware' => ['api'],
    ],
];
```

### Environment Variables

Add to your `.env` file:

```env
CBU_BASE_URL=https://cbu.uz/ru/arkhiv-kursov-valyut/json
CBU_CACHE_DURATION=60
CBU_DEFAULT_CURRENCY=USD
CBU_SCALE=2
CBU_SOURCE=database
CBU_LOG_ENABLED=true
CBU_ROUTES_PREFIX=api/cbu
```

### Data Source

The `CBU_SOURCE` configuration determines where currency rates are fetched from:

#### Option 1: Database (Recommended)
- **Value:** `database`
- **Pros:** Faster response, works offline, reduced API calls
- **Cons:** Requires periodic synchronization
- **Best for:** Production environments

When using `database` source, you need to:

**Step 1: Run Migrations**
```bash
php artisan migrate
```

This creates two tables:
- `currencies` - stores currency information (USD, EUR, etc.)
- `currency_rates` - stores daily exchange rates

**Step 2: Sync Currencies**
```bash
# Fetch and store all available currencies from CBU
php artisan cbu:sync-currencies
```

**Step 3: Fetch Exchange Rates**
```bash
# Fetch today's rates
php artisan cbu:fetch-rates

# Fetch rates for specific date
php artisan cbu:fetch-rates 2025-01-25

# Fetch yesterday's rates
php artisan cbu:fetch-rates yesterday
```

#### Option 2: API (Direct)
- **Value:** `api`
- **Pros:** Always fresh data, no database needed
- **Cons:** Slower response, requires internet connection
- **Best for:** Development or when real-time data is critical

When using `api` source:
- No migrations needed
- No synchronization required
- Data fetched directly from CBU API on each request

## 📖 Usage

### Currency Conversion

The `convert()` method provides a fluent interface for currency conversion with high precision using BCMath.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Basic conversion
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

echo $result->result;        // 94.11
echo $result->fromRate;      // 12705.00
echo $result->toRate;        // 13500.00
echo $result->amountInUzs;   // 1270500.00
```

#### Convert to UZS

```php
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('UZS')
    ->amount(100)
    ->get();

echo $result->result;  // 1270500.00
```

#### Convert from UZS

```php
$result = CbuCurrency::convert()
    ->from('UZS')
    ->to('USD')
    ->amount(1000000)
    ->get();

echo $result->result;  // 78.70
```

#### Cross Currency Conversion

```php
// USD to EUR through UZS
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Calculation: 100 USD * 12705 = 1270500 UZS
//              1270500 UZS / 13500 = 94.11 EUR
echo $result->result;        // 94.11
echo $result->amountInUzs;   // 1270500.00
```

#### Using Numeric Codes

```php
$result = CbuCurrency::convert()
    ->fromCode(840)  // USD
    ->toCode(978)    // EUR
    ->amount(100)
    ->get();
```

#### Specify Date

```php
// Specific date
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->date('2025-01-25')
    ->get();

// Today's date
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->today()
    ->get();
```

#### Change Data Source

```php
use Cbu\Currency\Enums\CurrencySource;

// Force API source
$result = CbuCurrency::convert()
    ->source(CurrencySource::API)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Force database source
$result = CbuCurrency::convert()
    ->source(CurrencySource::DATABASE)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();
```

#### With Caching

```php
// Cache result for 60 minutes
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->cache(60)
    ->get();
```

### Get Exchange Rates

The `rate()` method retrieves exchange rates for specific currencies and dates.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Get single currency rate for today
$rate = CbuCurrency::rate('USD');

echo $rate->ccy;      // "USD"
echo $rate->rate;     // 12705.00
echo $rate->diff;     // 15.25
echo $rate->date;     // "2025-11-09"
```

#### Get Rate for Specific Date

```php
// Get USD rate for specific date
$rate = CbuCurrency::rate('USD', '2025-01-25');

echo $rate->rate;  // 12705.00
echo $rate->date;  // "2025-01-25"
```

#### Get All Rates

```php
// Get all currencies rates for today
$rates = CbuCurrency::rate();

foreach ($rates as $rate) {
    echo "{$rate->ccy}: {$rate->rate}\n";
}
// USD: 12705.00
// EUR: 13500.00
// RUB: 130.00
```

#### Get All Rates for Specific Date

```php
// Get all rates for specific date
$rates = CbuCurrency::rate(null, '2025-01-25');

foreach ($rates as $rate) {
    echo "{$rate->ccy}: {$rate->rate} ({$rate->date})\n";
}
```

### Get Currencies List

The `currencies()` method retrieves available currency information.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Get all currencies
$currencies = CbuCurrency::currencies();

foreach ($currencies as $currency) {
    echo "{$currency->ccy} - {$currency->name_en}\n";
}
// USD - US Dollar
// EUR - Euro
// RUB - Russian Ruble
```

#### Get Specific Currency

```php
// Get single currency info
$currency = CbuCurrency::currencies('USD');

echo $currency->ccy;       // "USD"
echo $currency->name_en;   // "US Dollar"
echo $currency->name_uz;   // "AQSH dollari"
echo $currency->name_ru;   // "Доллар США"
echo $currency->code;      // 840
```

#### Get Currency Codes Only

```php
// Get array of currency codes
$codes = CbuCurrency::currencies()->pluck('ccy')->toArray();

// ['USD', 'EUR', 'RUB', 'GBP', 'JPY', ...]
```

### Sync Currencies

The `sync()` method synchronizes currencies and rates from CBU API to your database.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Sync currencies list
CbuCurrency::sync('currencies');

// Sync today's rates
CbuCurrency::sync('rates');

// Sync rates for specific date
CbuCurrency::sync('rates', '2025-01-25');
```

#### Sync Both Currencies and Rates

```php
// Sync both currencies and rates
CbuCurrency::sync('currencies');
CbuCurrency::sync('rates');
```

## 🔧 Artisan Commands

The package provides two Artisan commands for managing currency data.

### 1. Sync Currencies

Fetch and store all available currencies from CBU API to database.

```bash
php artisan cbu:sync-currencies
```

**What it does:**
- Fetches all currencies from CBU API
- Adds new currencies to `currencies` table
- Updates existing currency information

**Example output:**
```
Fetching currencies from CBU API...
Found 30 currencies
Synced: USD, EUR, RUB, GBP, JPY, CHF...
Currency synchronization completed successfully.
```

**When to use:**
- After initial installation
- When CBU adds new currencies
- Weekly maintenance (recommended)

### 2. Fetch Exchange Rates

Fetch and store exchange rates for a specific date.

```bash
# Fetch today's rates
php artisan cbu:fetch-rates

# Fetch for specific date
php artisan cbu:fetch-rates 2025-01-25

# Fetch yesterday's rates
php artisan cbu:fetch-rates yesterday

# Fetch for relative date
php artisan cbu:fetch-rates "1 week ago"
php artisan cbu:fetch-rates "2025-01-01"
```

**What it does:**
- Fetches exchange rates from CBU API for specified date
- Stores rates in `currency_rates` table
- Updates existing rates if already present

**Example output:**
```
Fetching rates for date: 2025-01-25
Found 30 rates
Saved: USD (12705.00), EUR (13500.00), RUB (130.00), GBP (16200.00)...
Currency rates fetched successfully.
```

**When to use:**
- Daily to keep rates up-to-date
- To fetch historical rates
- After syncing currencies

## ⏰ Auto Synchronization

For production environments, automate currency updates using Laravel's Task Scheduler.

### Laravel Scheduler

Add to `app/Console/Kernel.php`:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Fetch rates daily at 10:00 AM (after CBU updates)
        $schedule->command('cbu:fetch-rates')
            ->dailyAt('10:00')
            ->onFailure(function () {
                // Notify admin if sync fails
            });

        // Sync currencies every Monday at 9:00 AM
        $schedule->command('cbu:sync-currencies')
            ->weekly()
            ->mondays()
            ->at('09:00');
    }
}
```

### Activate Scheduler

Ensure the Laravel scheduler is running by adding this to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Alternative: Direct Cron Jobs

If not using Laravel Scheduler, add cron jobs directly:

```bash
# Fetch rates daily at 10:00 AM
0 10 * * * cd /path-to-your-project && php artisan cbu:fetch-rates

# Sync currencies every Monday at 9:00 AM
0 9 * * 1 cd /path-to-your-project && php artisan cbu:sync-currencies
```

### Docker/Kubernetes

For containerized environments, use supervisor or similar:

```ini
[program:cbu-scheduler]
command=/usr/bin/php /var/www/html/artisan schedule:work
autostart=true
autorestart=true
```

## 🗄️ Database Structure

### Currencies Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| ccy | string | Currency code (unique) - USD, EUR, RUB |
| name_uz | string | Name in Uzbek - AQSH dollari |
| name_oz | string | Name in Uzbek (Cyrillic) - АҚШ доллари |
| name_ru | string | Name in Russian - Доллар США |
| name_en | string | Name in English - US Dollar |
| code | string | Numeric code - 840 |
| cbu_id | string | CBU identifier |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Update time |

### Currency Rates Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| currency_id | bigint | Foreign key to currencies |
| date | date | Rate date (indexed) |
| currency_date | date | Original CBU date |
| rate | decimal(15,4) | Exchange rate - 12705.0000 |
| diff | decimal(15,4) | Difference from previous - 15.2500 |
| nominal | integer | Nominal value - 1 |
| created_at | timestamp | Creation time |
| updated_at | timestamp | Update time |

**Indexes:**
- `date` - For fast lookups
- `['currency_id', 'date']` - Composite index

**Unique constraint:** `['currency_id', 'date']` - Only one rate per currency per day

## 🧪 Testing

The package provides full test coverage using Pest PHP framework.

### Running Tests

```bash
# All tests
composer test

# Unit tests only
vendor/bin/pest --testsuite=Unit

# Feature tests only
vendor/bin/pest --testsuite=Feature

# Verbose mode
vendor/bin/pest --verbose
```

For detailed testing documentation, see [TESTING.md](TESTING.md)

## 🌐 API Endpoints

The package automatically registers RESTful API endpoints for accessing currency data.

**Base URL:** `{your-domain}/api/cbu`

### 1. Get All Currencies

```http
GET /api/cbu/currencies
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": [
    {
      "ccy": "USD",
      "name_uz": "AQSH dollari",
      "name_en": "US Dollar",
      "code": 840
    },
    {
      "ccy": "EUR",
      "name_uz": "EVRO",
      "name_en": "Euro",
      "code": 978
    }
  ]
}
```

### 2. Get Currency Codes

```http
GET /api/cbu/currencies/codes
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": ["USD", "EUR", "RUB", "GBP", "JPY", "CHF"]
}
```

### 3. Get Specific Currency

```http
GET /api/cbu/currencies/{code}
```

**Example:**
```http
GET /api/cbu/currencies/USD
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": {
    "ccy": "USD",
    "name_uz": "AQSH dollari",
    "name_en": "US Dollar",
    "name_ru": "Доллар США",
    "code": 840
  }
}
```

### 4. Get Today's Rates

```http
GET /api/cbu/rates/today
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": [
    {
      "ccy": "USD",
      "rate": 12705.00,
      "diff": 15.25,
      "date": "2025-11-09"
    },
    {
      "ccy": "EUR",
      "rate": 13500.00,
      "diff": -10.50,
      "date": "2025-11-09"
    }
  ]
}
```

### 5. Get Rates by Date

```http
GET /api/cbu/rates?date={date}
```

**Query Parameters:**
- `date` (optional): Date in `Y-m-d` format (e.g., `2025-01-25`)

**Example:**
```http
GET /api/cbu/rates?date=2025-01-25
```

### 6. Get Specific Currency Rate

```http
GET /api/cbu/rates/{code}?date={date}
```

**Example:**
```http
GET /api/cbu/rates/USD?date=2025-01-15
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": {
    "ccy": "USD",
    "rate": 12705.00,
    "diff": 15.25,
    "nominal": 1,
    "date": "2025-01-15"
  }
}
```

### 7. Convert Currency

```http
POST /api/cbu/convert
Content-Type: application/json
```

**Request Body:**
```json
{
  "amount": 100,
  "from": "USD",
  "to": "EUR",
  "date": "2025-01-15"
}
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": {
    "amount": 100,
    "from_currency": "USD",
    "to_currency": "EUR",
    "result": 94.11,
    "from_rate": 12705.00,
    "to_rate": 13500.00,
    "amount_in_uzs": 1270500.00,
    "date": "2025-01-15"
  }
}
```

**Validation Rules:**
- `amount` - required, numeric, minimum 0.01
- `from` - required, valid 3-letter currency code
- `to` - required, valid 3-letter currency code
- `date` - optional, Y-m-d format, cannot be future date

### 8. Get Conversion Rate

```http
GET /api/cbu/convert/rate/{from}/{to}?date={date}
```

Returns the conversion rate for 1 unit of source currency.

**Example:**
```http
GET /api/cbu/convert/rate/USD/EUR?date=2025-01-15
```

**Response:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": {
    "amount": 1,
    "from_currency": "USD",
    "to_currency": "EUR",
    "result": 0.94,
    "from_rate": 12705.00,
    "to_rate": 13500.00,
    "amount_in_uzs": 12705.00,
    "date": "2025-01-15"
  }
}
```

### Error Responses

All endpoints return a consistent error format:

```json
{
  "msg": "Currency not found",
  "error": "The currency code 'XYZ' does not exist",
  "success": false,
  "data": []
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation failed)
- `404` - Not Found (currency or rate not found)
- `500` - Internal Server Error

### Customizing API Routes

You can customize the API route prefix in `config/cbu-currency.php`:

```php
'routes' => [
    'prefix' => env('CBU_ROUTES_PREFIX', 'api/cbu'),
    'middleware' => ['api'],
],
```

Or in `.env`:

```env
CBU_ROUTES_PREFIX=api/v1/currency
```

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Nurbek Jummayev**
- GitHub: [@nurbekjummayev](https://github.com/nurbekjummayev)
- Email: jummayevnurbek279@gmail.com

## 🔗 Useful Links

- [CBU Official Website](https://cbu.uz/)
- [CBU API Documentation](https://cbu.uz/uz/arkhiv-kursov-valyut/veb-masteram/)
- [Laravel Documentation](https://laravel.com/docs)
