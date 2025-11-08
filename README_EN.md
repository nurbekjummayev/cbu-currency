# CBU Currency - Central Bank of Uzbekistan Exchange Rates

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1%7C%5E8.2%7C%5E8.3%7C%5E8.4-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10%7C%5E11%7C%5E12-red)](https://laravel.com/)
[![Tests](https://img.shields.io/badge/tests-43%20passed-brightgreen)](https://pestphp.com/)

A Laravel package for working with Central Bank of Uzbekistan (CBU) currency exchange rates. This package provides easy-to-use methods for fetching, storing, and converting currencies with high precision using BCMath.

**English version** | [O'zbek versiyasi](README.md)

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
  - [Builder Pattern](#1-builder-pattern)
  - [API Endpoints](#2-api-endpoints)
  - [Artisan Commands](#3-artisan-commands)
- [Database Structure](#-database-structure)
- [Testing](#-testing)
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
composer require cbu/currency
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=cbu-currency-config
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Sync Currencies (Optional)

```bash
# Update currency list
php artisan cbu:sync-currencies

# Fetch today's rates
php artisan cbu:fetch-rates
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

`CBU_SOURCE` determines where currency rates are fetched from:

- **`database`** (recommended): Fetch from local database - faster, works offline
- **`api`**: Fetch directly from CBU API each time - fresh data, but slower

## 📖 Usage

### 1. Builder Pattern

For flexible conversions, use the Builder pattern:

```php
use Cbu\Currency\Facades\CbuCurrency;

// Simple conversion
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->date('2025-01-25')
    ->get();

echo $result->amount;        // 100
echo $result->fromCurrency;  // "USD"
echo $result->toCurrency;    // "EUR"
echo $result->result;        // 94.11
echo $result->fromRate;      // 12705.00
echo $result->toRate;        // 13500.00
echo $result->amountInUzs;   // 1270500.00
echo $result->date;          // "2025-01-25"
```

#### Using Numeric Codes

```php
// Using numeric currency codes
$result = CbuCurrency::convert()
    ->fromCode(840)  // USD numeric code
    ->toCode(978)    // EUR numeric code
    ->amount(100)
    ->get();
```

#### Today's Date

```php
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->today()
    ->get();
```

#### With Caching

```php
// Cache for 60 minutes
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->cache(60)
    ->get();
```

#### Change Data Source

```php
use Cbu\Currency\Enums\CurrencySource;

// Fetch from API
$result = CbuCurrency::convert()
    ->source(CurrencySource::API)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Fetch from database
$result = CbuCurrency::convert()
    ->source(CurrencySource::DATABASE)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();
```

#### Convert to UZS

```php
// USD to UZS
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('UZS')
    ->amount(100)
    ->get();

echo $result->result;        // 1270500.00
```

#### Convert from UZS

```php
// UZS to USD
$result = CbuCurrency::convert()
    ->from('UZS')
    ->to('USD')
    ->amount(1000000)
    ->get();

echo $result->result;        // 78.70
```

#### Cross Currency Conversion

```php
// USD to EUR
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

### 2. API Endpoints

The package automatically registers RESTful API endpoints.

**Base URL:** `{your-domain}/api/cbu`

#### 1. Get All Currencies

```http
GET /api/cbu/currencies
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "ccy": "USD",
      "name_uz": "AQSH dollari",
      "name_en": "US Dollar",
      "code": 840
    }
  ]
}
```

#### 2. Get Currency Codes

```http
GET /api/cbu/currencies/codes
```

**Response:**
```json
{
  "success": true,
  "data": ["USD", "EUR", "RUB", "GBP", ...]
}
```

#### 3. Get Specific Currency

```http
GET /api/cbu/currencies/USD
```

**Response:**
```json
{
  "success": true,
  "data": {
    "ccy": "USD",
    "name_uz": "AQSH dollari",
    "name_en": "US Dollar",
    "code": 840
  }
}
```

#### 4. Get Today's Rates

```http
GET /api/cbu/rates/today
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "ccy": "USD",
      "rate": 12705.00,
      "diff": 15.25,
      "date": "2025-11-03"
    }
  ]
}
```

#### 5. Get Rates by Date

```http
GET /api/cbu/rates?date=2025-01-15
```

**Query Parameters:**
- `date` (optional): Date in Y-m-d format

#### 6. Get Specific Currency Rate

```http
GET /api/cbu/rates/USD?date=2025-01-15
```

**Response:**
```json
{
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

#### 7. Convert Currency (POST)

```http
POST /api/cbu/convert
Content-Type: application/json

{
  "amount": 100,
  "from": "USD",
  "to": "UZS",
  "date": "2025-01-15"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "amount": 100,
    "from_currency": "USD",
    "to_currency": "UZS",
    "result": 1270500,
    "from_rate": 12705,
    "to_rate": 1,
    "amount_in_uzs": 1270500,
    "date": "2025-01-15"
  }
}
```

**Validation:**
- `amount` - required, numeric, minimum 0.01
- `from` - required, 3-letter currency code
- `to` - required, 3-letter currency code
- `date` - optional, Y-m-d format, cannot be future date

#### 8. Get Conversion Rate

```http
GET /api/cbu/convert/rate/USD/EUR?date=2025-01-15
```

Returns the conversion rate for 1 unit of currency.

**Response:**
```json
{
  "success": true,
  "data": {
    "amount": 1,
    "from_currency": "USD",
    "to_currency": "EUR",
    "result": 0.94,
    "from_rate": 12705,
    "to_rate": 13500,
    "amount_in_uzs": 12705,
    "date": "2025-01-15"
  }
}
```

#### Error Responses

All endpoints return consistent error format:

```json
{
  "success": false,
  "errorMessage": "Currency conversion failed",
  "error": "Detailed error message"
}
```

### 3. Artisan Commands

#### Sync Currencies

Update the currency list from CBU:

```bash
php artisan cbu:sync-currencies
```

This command:
- Fetches all currencies from CBU API
- Adds new currencies to database
- Updates existing currencies

**Output:**
```
Fetching currencies from CBU API...
Found 30 currencies
Synced: USD, EUR, RUB, GBP...
Currency synchronization completed successfully.
```

#### Fetch Rates

Fetch and store currency rates for specific date:

```bash
# Fetch today's rates
php artisan cbu:fetch-rates

# Fetch for specific date
php artisan cbu:fetch-rates 2025-01-25

# Fetch for yesterday
php artisan cbu:fetch-rates yesterday

# Fetch for 1 week ago
php artisan cbu:fetch-rates "1 week ago"
```

This command:
- Fetches rates from CBU API for specified date
- Saves rate for each currency to database
- Updates existing rates

**Output:**
```
Fetching rates for date: 2025-01-25
Found 30 rates
Saved: USD (12705.00), EUR (13500.00), RUB (130.00)...
Currency rates fetched successfully.
```

#### Auto Synchronization

Use Laravel Scheduler for automatic daily updates.

In `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Update rates daily at 10:00 AM
    $schedule->command('cbu:fetch-rates')
        ->dailyAt('10:00');

    // Sync currencies every Monday at 9:00 AM
    $schedule->command('cbu:sync-currencies')
        ->weekly()
        ->mondays()
        ->at('09:00');
}
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

## 📄 License

MIT License. See [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Jummayev Nurbek**
- GitHub: [@Jummayev](https://github.com/Jummayev)
- Email: jummayevnurbek279@gmail.com

## 🔗 Useful Links

- [CBU Official Website](https://cbu.uz/)
- [CBU API Documentation](https://cbu.uz/ru/arkhiv-kursov-valyut/json/)
- [Laravel Documentation](https://laravel.com/docs)
- [Pest PHP Documentation](https://pestphp.com/docs)

---

<div align="center">
Made with ❤️ in Uzbekistan
</div>
