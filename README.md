# CBU Currency - O'zbekiston Markaziy Banki Valyuta Kurslari

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1%7C%5E8.2%7C%5E8.3%7C%5E8.4-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10%7C%5E11%7C%5E12-red)](https://laravel.com/)
[![Tests](https://img.shields.io/badge/tests-43%20passed-brightgreen)](https://pestphp.com/)

O'zbekiston Markaziy Banki (CBU) valyuta kurslari bilan ishlash uchun Laravel paketi. Bu paket valyuta kurslarini olish, saqlash va yuqori aniqlik bilan konvertatsiya qilish uchun qulay metodlarni taqdim etadi.

[English version](README_EN.md) | **O'zbek versiyasi**

## 📋 Mundarija

- [Xususiyatlar](#-xususiyatlar)
- [Talablar](#-talablar)
- [O'rnatish](#-ornatish)
- [Konfiguratsiya](#-konfiguratsiya)
- [Foydalanish](#-foydalanish)
  - [Builder Pattern](#1-builder-pattern)
  - [API Endpointlar](#2-api-endpointlar)
  - [Artisan Buyruqlar](#3-artisan-buyruqlar)
- [Database Strukturasi](#-database-strukturasi)
- [Testing](#-testing)
- [Litsenziya](#-litsenziya)

## ✨ Xususiyatlar

- 📊 **CBU API Integratsiyasi** - Markaziy bank API dan valyuta kurslarini olish
- 💱 **Yuqori Aniqlikda Konvertatsiya** - BCMath yordamida aniq hisob-kitoblar
- 🗄️ **Database Saqlash** - Tarixiy kurslarni saqlash va tez kirish
- 🎯 **Oddiy API** - Intuitive va qulay interfeys
- ⚙️ **Sozlanishi** - To'liq konfiguratsiya imkoniyati
- 🔄 **Avtomatik Sinxronizatsiya** - Valyutalarni avtomatik yangilash
- 📅 **Tarixiy Ma'lumotlar** - Istalgan sana uchun kurslar
- 🚀 **RESTful API** - To'liq REST API endpointlar
- ✅ **To'liq Test Qamrovi** - 43 ta test bilan qoplangan
- 🎨 **Fluent Interface** - Chiroyli va o'qilishi oson kod

## 📦 Talablar

- PHP ^8.1|^8.2|^8.3|^8.4
- Laravel ^10.0|^11.0|^12.0
- BCMath PHP Extension
- GuzzleHTTP ^7.0

## 🚀 O'rnatish

### 1. Composer orqali o'rnatish

```bash
composer require cbu/currency
```

### 2. Konfiguratsiya faylini chiqarish

```bash
php artisan vendor:publish --tag=cbu-currency-config
```

### 3. Migratsiyalarni ishga tushirish

```bash
php artisan migrate
```

### 4. Valyutalarni sinxronlash (ixtiyoriy)

```bash
# Valyutalar ro'yxatini yangilash
php artisan cbu:sync-currencies

# Bugungi kurslarni olish
php artisan cbu:fetch-rates
```

## ⚙️ Konfiguratsiya

Konfiguratsiya fayli: `config/cbu-currency.php`

```php
return [
    // CBU API bazaviy URL
    'base_url' => env('CBU_BASE_URL', 'https://cbu.uz/ru/arkhiv-kursov-valyut/json'),

    // Kesh muddati (daqiqalarda)
    'cache_duration' => env('CBU_CACHE_DURATION', 60),

    // Default valyuta kodi
    'default_currency' => env('CBU_DEFAULT_CURRENCY', 'USD'),

    // BCMath hisob-kitob aniqligi (o'nlik raqamlar soni)
    'scale' => env('CBU_SCALE', 2),

    // Ma'lumot manbai: 'database' yoki 'api'
    'source' => env('CBU_SOURCE', 'database'),

    // Logging yoqish/o'chirish
    'log_enabled' => env('CBU_LOG_ENABLED', true),

    // API routes sozlamalari
    'routes' => [
        'prefix' => env('CBU_ROUTES_PREFIX', 'api/cbu'),
        'middleware' => ['api'],
    ],
];
```

### Environment o'zgaruvchilari

`.env` faylingizga qo'shing:

```env
CBU_BASE_URL=https://cbu.uz/ru/arkhiv-kursov-valyut/json
CBU_CACHE_DURATION=60
CBU_DEFAULT_CURRENCY=USD
CBU_SCALE=2
CBU_SOURCE=database
CBU_LOG_ENABLED=true
CBU_ROUTES_PREFIX=api/cbu
```

### Ma'lumot Manbai

`CBU_SOURCE` qiymati valyuta kurslarini qayerdan olishni belgilaydi:

- **`database`** (tavsiya etiladi): Mahalliy bazadan oladi - tezroq, offline ishlaydi
- **`api`**: Har safar CBU API dan to'g'ridan-to'g'ri oladi - yangi ma'lumot, lekin sekinroq

## 📖 Foydalanish

### 1. Builder Pattern

Yanada moslashuvchan konvertatsiya uchun Builder pattern ishlatishingiz mumkin:

```php
use Cbu\Currency\Facades\CbuCurrency;

// Oddiy konvertatsiya
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

#### Valyuta kodlari orqali konvertatsiya

```php
// Numeric code orqali
$result = CbuCurrency::convert()
    ->fromCode(840)  // USD numeric code
    ->toCode(978)    // EUR numeric code
    ->amount(100)
    ->get();
```

#### Bugungi sana

```php
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->today()
    ->get();
```

#### Kesh bilan

```php
// 60 daqiqa kesh
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->cache(60)
    ->get();
```

#### Ma'lumot manbaini o'zgartirish

```php
use Cbu\Currency\Enums\CurrencySource;

// API dan olish
$result = CbuCurrency::convert()
    ->source(CurrencySource::API)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Database dan olish
$result = CbuCurrency::convert()
    ->source(CurrencySource::DATABASE)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();
```

#### So'mga konvertatsiya

```php
// USD ni UZS ga
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('UZS')
    ->amount(100)
    ->get();

echo $result->result;        // 1270500.00
```

#### So'mdan konvertatsiya

```php
// UZS ni USD ga
$result = CbuCurrency::convert()
    ->from('UZS')
    ->to('USD')
    ->amount(1000000)
    ->get();

echo $result->result;        // 78.70
```

#### Ikki xorijiy valyuta o'rtasida

```php
// USD ni EUR ga
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Hisob-kitob: 100 USD * 12705 = 1270500 UZS
//              1270500 UZS / 13500 = 94.11 EUR
echo $result->result;        // 94.11
echo $result->amountInUzs;   // 1270500.00
```

### 2. API Endpointlar

Paket avtomatik ravishda RESTful API endpointlarni ro'yxatdan o'tkazadi.

**Base URL:** `{your-domain}/api/cbu`

#### 1. Barcha Valyutalar

```http
GET /api/cbu/currencies
```

**Javob:**
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

#### 2. Valyuta Kodlari

```http
GET /api/cbu/currencies/codes
```

**Javob:**
```json
{
  "success": true,
  "data": ["USD", "EUR", "RUB", "GBP", ...]
}
```

#### 3. Ma'lum Valyuta

```http
GET /api/cbu/currencies/USD
```

**Javob:**
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

#### 4. Bugungi Kurslar

```http
GET /api/cbu/rates/today
```

**Javob:**
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

#### 5. Sana Bo'yicha Kurslar

```http
GET /api/cbu/rates?date=2025-01-15
```

**Query Parametrlar:**
- `date` (ixtiyoriy): Y-m-d formatidagi sana

#### 6. Ma'lum Valyuta Kursi

```http
GET /api/cbu/rates/USD?date=2025-01-15
```

**Javob:**
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

#### 7. Konvertatsiya (POST)

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

**Javob:**
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
- `amount` - majburiy, numeric, minimum 0.01
- `from` - majburiy, 3 harfli valyuta kodi
- `to` - majburiy, 3 harfli valyuta kodi
- `date` - ixtiyoriy, Y-m-d formatida, kelajak sana bo'lmasligi kerak

#### 8. Konvertatsiya Kursi

```http
GET /api/cbu/convert/rate/USD/EUR?date=2025-01-15
```

1 birlik valyutaning konvertatsiya kursini qaytaradi.

**Javob:**
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

#### Xato Javoblar

Barcha endpointlar bir xil xato formatini qaytaradi:

```json
{
  "success": false,
  "errorMessage": "Valyuta konvertatsiyasi muvaffaqiyatsiz",
  "error": "Batafsil xato xabari"
}
```

### 3. Artisan Buyruqlar

#### Valyutalarni Sinxronlash

CBU dan valyutalar ro'yxatini yangilash:

```bash
php artisan cbu:sync-currencies
```

Bu buyruq:
- CBU API dan barcha valyutalarni oladi
- Yangi valyutalarni database ga qo'shadi
- Mavjud valyutalarni yangilaydi

**Chiqish:**
```
Fetching currencies from CBU API...
Found 30 currencies
Synced: USD, EUR, RUB, GBP...
Currency synchronization completed successfully.
```

#### Kurslarni Olish

Ma'lum sana uchun valyuta kurslarini olish va saqlash:

```bash
# Bugungi kurslarni olish
php artisan cbu:fetch-rates

# Ma'lum sana uchun
php artisan cbu:fetch-rates 2025-01-25

# Kecha uchun
php artisan cbu:fetch-rates yesterday

# 1 hafta oldin
php artisan cbu:fetch-rates "1 week ago"
```

Bu buyruq:
- CBU API dan belgilangan sana uchun kurslarni oladi
- Har bir valyuta uchun kursni database ga saqlaydi
- Mavjud kurslarni yangilaydi

**Chiqish:**
```
Fetching rates for date: 2025-01-25
Found 30 rates
Saved: USD (12705.00), EUR (13500.00), RUB (130.00)...
Currency rates fetched successfully.
```

#### Avtomatik Sinxronizatsiya

Kunlik avtomatik yangilanish uchun Laravel Scheduler'dan foydalaning.

`app/Console/Kernel.php` faylida:

```php
protected function schedule(Schedule $schedule)
{
    // Har kuni soat 10:00 da kurslarni yangilash
    $schedule->command('cbu:fetch-rates')
        ->dailyAt('10:00');

    // Har dushanba kuni valyutalarni sinxronlash
    $schedule->command('cbu:sync-currencies')
        ->weekly()
        ->mondays()
        ->at('09:00');
}
```

## 🗄️ Database Strukturasi

### Currencies Jadvali

| Ustun | Tur | Tavsif |
|-------|-----|--------|
| id | bigint | Primary key |
| ccy | string | Valyuta kodi (unique) - USD, EUR, RUB |
| name_uz | string | O'zbek nomlanishi - AQSH dollari |
| name_oz | string | O'zbek nomlanishi (Kirill) - АҚШ доллари |
| name_ru | string | Rus nomlanishi - Доллар США |
| name_en | string | Ingliz nomlanishi - US Dollar |
| code | string | Raqamli kod - 840 |
| cbu_id | string | CBU identifikatori |
| created_at | timestamp | Yaratilgan vaqt |
| updated_at | timestamp | Yangilangan vaqt |

### Currency Rates Jadvali

| Ustun | Tur | Tavsif |
|-------|-----|--------|
| id | bigint | Primary key |
| currency_id | bigint | Valyutaga foreign key |
| date | date | Kurs sanasi (indexed) |
| currency_date | date | CBU original sanasi |
| rate | decimal(15,4) | Ayirboshlash kursi - 12705.0000 |
| diff | decimal(15,4) | Oldingi kundan farq - 15.2500 |
| nominal | integer | Nominal qiymat - 1 |
| created_at | timestamp | Yaratilgan vaqt |
| updated_at | timestamp | Yangilangan vaqt |

**Indexlar:**
- `date` - Tez qidirish uchun
- `['currency_id', 'date']` - Composite index

**Unique constraint:** `['currency_id', 'date']` - Bir valyuta uchun bir kunda faqat bitta kurs

## 🧪 Testing

Paket Pest PHP test framework yordamida to'liq test qamrovini ta'minlaydi.

### Testlarni Ishga Tushirish

```bash
# Barcha testlar
composer test

# Faqat Unit testlar
vendor/bin/pest --testsuite=Unit

# Faqat Feature testlar
vendor/bin/pest --testsuite=Feature

# Verbose rejimda
vendor/bin/pest --verbose
```

Batafsil test hujjatlari: [TESTING.md](TESTING.md)

## 📄 Litsenziya

MIT litsenziyasi. Batafsil [LICENSE](LICENSE) faylida.

## 👨‍💻 Muallif

**Jummayev Nurbek**
- GitHub: [@Jummayev](https://github.com/Jummayev)
- Email: jummayevnurbek279@gmail.com

## 🔗 Foydali Havolalar

- [CBU Rasmiy Sayti](https://cbu.uz/)
- [CBU API Hujjatlari](https://cbu.uz/ru/arkhiv-kursov-valyut/json/)
- [Laravel Hujjatlari](https://laravel.com/docs)
- [Pest PHP Hujjatlari](https://pestphp.com/docs)

---

<div align="center">
Made with ❤️ in Uzbekistan
</div>
