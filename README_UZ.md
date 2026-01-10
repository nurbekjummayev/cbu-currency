# CBU Currency - O'zbekiston Markaziy Banki Valyuta Kurslari

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1%7C%5E8.2%7C%5E8.3%7C%5E8.4-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-%5E10%7C%5E11%7C%5E12-red)](https://laravel.com/)
[![Tests](https://img.shields.io/badge/tests-43%20passed-brightgreen)](https://pestphp.com/)

O'zbekiston Markaziy Banki (CBU) valyuta kurslari bilan oson va qulay ishlash uchun mo'ljallangan Laravel paketi. Bu paket valyuta kurslarini olish, saqlash va yuqori aniqlikda (BCMath) konvertatsiya qilish uchun qulay metodlarni taqdim etadi.

[English version](README.md) | **O'zbek versiyasi**

## 📋 Mundarija

- [Asosiy Xususiyatlar](#-asosiy-xususiyatlar)
- [Talablar](#-talablar)
- [O'rnatish](#-ornatish)
- [Konfiguratsiya](#-konfiguratsiya)
- [Foydalanish](#-foydalanish)
- [Artisan Buyruqlar](#-artisan-buyruqlar)
- [Avtomatik Sinxronizatsiya](#-avtomatik-sinxronizatsiya)
- [Ma'lumotlar Bazasi Strukturasi](#-malumotlar-bazasi-strukturasi)
- [Testlash](#-testlash)
- [API Endpoints](#-api-endpoints)
- [Litsenziya](#-litsenziya)

## ✨ Asosiy Xususiyatlar

- 📊 **CBU API Integratsiyasi** - Markaziy Bank API dan valyuta kurslarini olish
- 💱 **Yuqori Aniqlikda Konvertatsiya** - BCMath yordamida aniq hisob-kitoblar
- 🗄️ **Ma'lumotlar Bazasi** - Tarixiy kurslarni saqlash va tez kirish
- 🎯 **Oddiy API** - Intuitiv va qulay interfeys
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
composer require nurbekjummayev/cbu-currency
```

### 2. Konfiguratsiya va Migratsiyalarni Chiqarish

```bash
# Konfiguratsiya faylini chiqarish
php artisan vendor:publish --tag=cbu-currency-config

# Migratsiyalarni chiqarish (ixtiyoriy, avtomatik yuklanadi)
php artisan vendor:publish --tag=cbu-currency-migrations
```

## ⚙️ Konfiguratsiya

Konfiguratsiya fayli: `config/cbu-currency.php`

```php
return [
    // CBU API bazaviy URL manzili
    'base_url' => env('CBU_BASE_URL', 'https://cbu.uz/ru/arkhiv-kursov-valyut/json'),

    // Kesh muddati (daqiqalarda)
    'cache_duration' => env('CBU_CACHE_DURATION', 60),

    // Standart valyuta kodi
    'default_currency' => env('CBU_DEFAULT_CURRENCY', 'USD'),

    // BCMath hisob-kitob aniqligi (o'nlik raqamlar soni)
    'scale' => env('CBU_SCALE', 2),

    // Ma'lumot manbai: 'database' yoki 'api'
    'source' => env('CBU_SOURCE', 'database'),

    // Loglarni yozishni yoqish/o'chirish
    'log_enabled' => env('CBU_LOG_ENABLED', true),

    // API routes sozlamalari
    'routes' => [
        'prefix' => env('CBU_ROUTES_PREFIX', 'api/cbu'),
        'middleware' => ['api'],
    ],
];
```

### Environment O'zgaruvchilari

`.env` faylingizga quyidagilarni qo'shing:

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

`CBU_SOURCE` konfiguratsiyasi valyuta kurslarini qayerdan olishni belgilaydi:

#### Variant 1: Database (Tavsiya etiladi)
- **Qiymat:** `database`
- **Afzalliklari:** Tezroq javob, offline ishlaydi, API chaqiruvlarini kamaytiradi
- **Kamchiliklari:** Davriy sinxronizatsiya talab qiladi
- **Qayerda ishlatish:** Prodakshn muhitda

`database` manbaidan foydalanganda quyidagilarni bajaring:

**1-qadam: Migratsiyalarni Ishga Tushiring**
```bash
php artisan migrate
```

Bu ikkita jadval yaratadi:
- `currencies` - valyuta ma'lumotlarini saqlaydi (USD, EUR va h.k.)
- `currency_rates` - kunlik ayirboshlash kurslarini saqlaydi

**2-qadam: Valyutalarni Sinxronlash**
```bash
# CBU dan barcha mavjud valyutalarni olish va saqlash
php artisan cbu:sync-currencies
```

**3-qadam: Ayirboshlash Kurslarini Olish**
```bash
# Bugungi kurslarni olish
php artisan cbu:fetch-rates

# Ma'lum sana uchun kurslarni olish
php artisan cbu:fetch-rates 2025-01-25

# Kechagi kurslarni olish
php artisan cbu:fetch-rates yesterday
```

#### Variant 2: API (To'g'ridan-to'g'ri)
- **Qiymat:** `api`
- **Afzalliklari:** Har doim yangi ma'lumotlar, ma'lumotlar bazasi kerak emas
- **Kamchiliklari:** Sekinroq javob, internet aloqasi talab qiladi
- **Qayerda ishlatish:** Development muhitida yoki real-time ma'lumot kerak bo'lganda

`api` manbaidan foydalanganda:
- Migratsiyalar kerak emas
- Sinxronizatsiya kerak emas
- Har bir so'rovda ma'lumotlar to'g'ridan-to'g'ri CBU API dan olinadi

## 📖 Foydalanish

### Valyuta Konvertatsiyasi

`convert()` metodi yuqori aniqlikda (BCMath) valyuta konvertatsiyasi uchun fluent interfeys taqdim etadi.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Asosiy konvertatsiya
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

#### So'mga Konvertatsiya

```php
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('UZS')
    ->amount(100)
    ->get();

echo $result->result;  // 1270500.00
```

#### So'mdan Konvertatsiya

```php
$result = CbuCurrency::convert()
    ->from('UZS')
    ->to('USD')
    ->amount(1000000)
    ->get();

echo $result->result;  // 78.70
```

#### Ikki Xorijiy Valyuta O'rtasida

```php
// USD dan EUR ga (UZS orqali)
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Hisoblash: 100 USD * 12705 = 1270500 UZS
//            1270500 UZS / 13500 = 94.11 EUR
echo $result->result;        // 94.11
echo $result->amountInUzs;   // 1270500.00
```

#### Raqamli Kodlar Orqali

```php
$result = CbuCurrency::convert()
    ->fromCode(840)  // USD
    ->toCode(978)    // EUR
    ->amount(100)
    ->get();
```

#### Sana Ko'rsatish

```php
// Ma'lum sana uchun
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->date('2025-01-25')
    ->get();

// Bugungi sana uchun
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->today()
    ->get();
```

#### Ma'lumot Manbaini O'zgartirish

```php
use Cbu\Currency\Enums\CurrencySource;

// API manbaidan majburiy foydalanish
$result = CbuCurrency::convert()
    ->source(CurrencySource::API)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();

// Database manbaidan majburiy foydalanish
$result = CbuCurrency::convert()
    ->source(CurrencySource::DATABASE)
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->get();
```

#### Kesh Bilan

```php
// Natijani 60 daqiqa keshda saqlash
$result = CbuCurrency::convert()
    ->from('USD')
    ->to('EUR')
    ->amount(100)
    ->cache(60)
    ->get();
```

### Ayirboshlash Kurslarini Olish

`rate()` metodi ma'lum valyutalar va sanalar uchun ayirboshlash kurslarini oladi.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Bugun uchun bitta valyuta kursi
$rate = CbuCurrency::rate('USD');

echo $rate->ccy;      // "USD"
echo $rate->rate;     // 12705.00
echo $rate->diff;     // 15.25
echo $rate->date;     // "2025-11-09"
```

#### Ma'lum Sana Uchun Kurs

```php
// USD kursi ma'lum sana uchun
$rate = CbuCurrency::rate('USD', '2025-01-25');

echo $rate->rate;  // 12705.00
echo $rate->date;  // "2025-01-25"
```

#### Barcha Kurslar

```php
// Bugun uchun barcha valyuta kurslari
$rates = CbuCurrency::rate();

foreach ($rates as $rate) {
    echo "{$rate->ccy}: {$rate->rate}\n";
}
// USD: 12705.00
// EUR: 13500.00
// RUB: 130.00
```

#### Ma'lum Sana Uchun Barcha Kurslar

```php
// Ma'lum sana uchun barcha kurslar
$rates = CbuCurrency::rate(null, '2025-01-25');

foreach ($rates as $rate) {
    echo "{$rate->ccy}: {$rate->rate} ({$rate->date})\n";
}
```

### Valyutalar Ro'yxatini Olish

`currencies()` metodi mavjud valyuta ma'lumotlarini oladi.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Barcha valyutalar
$currencies = CbuCurrency::currencies();

foreach ($currencies as $currency) {
    echo "{$currency->ccy} - {$currency->name_uz}\n";
}
// USD - AQSH dollari
// EUR - EVRO
// RUB - Rossiya rubli
```

#### Ma'lum Valyuta

```php
// Bitta valyuta ma'lumoti
$currency = CbuCurrency::currencies('USD');

echo $currency->ccy;       // "USD"
echo $currency->name_en;   // "US Dollar"
echo $currency->name_uz;   // "AQSH dollari"
echo $currency->name_ru;   // "Доллар США"
echo $currency->code;      // 840
```

#### Faqat Valyuta Kodlari

```php
// Valyuta kodlari massivini olish
$codes = CbuCurrency::currencies()->pluck('ccy')->toArray();

// ['USD', 'EUR', 'RUB', 'GBP', 'JPY', ...]
```

### Valyutalarni Sinxronlash

`sync()` metodi valyutalar va kurslarni CBU API dan ma'lumotlar bazasiga sinxronlashtiradi.

```php
use Cbu\Currency\Facades\CbuCurrency;

// Valyutalar ro'yxatini sinxronlash
CbuCurrency::sync('currencies');

// Bugungi kurslarni sinxronlash
CbuCurrency::sync('rates');

// Ma'lum sana uchun kurslarni sinxronlash
CbuCurrency::sync('rates', '2025-01-25');
```

#### Valyutalar va Kurslarni Birga Sinxronlash

```php
// Valyutalar va kurslarni birga sinxronlash
CbuCurrency::sync('currencies');
CbuCurrency::sync('rates');
```

## 🔧 Artisan Buyruqlar

Paket valyuta ma'lumotlarini boshqarish uchun ikkita Artisan buyrug'ini taqdim etadi.

### 1. Valyutalarni Sinxronlash

CBU API dan barcha mavjud valyutalarni olish va ma'lumotlar bazasiga saqlash.

```bash
php artisan cbu:sync-currencies
```

**Bu buyruq nima qiladi:**
- CBU API dan barcha valyutalarni oladi
- Yangi valyutalarni `currencies` jadvaliga qo'shadi
- Mavjud valyuta ma'lumotlarini yangilaydi

**Natija namunasi:**
```
Fetching currencies from CBU API...
Found 30 currencies
Synced: USD, EUR, RUB, GBP, JPY, CHF...
Currency synchronization completed successfully.
```

**Qachon ishlatish kerak:**
- Dastlabki o'rnatishdan keyin
- CBU yangi valyutalar qo'shganda
- Haftalik texnik xizmat ko'rsatishda (tavsiya etiladi)

### 2. Ayirboshlash Kurslarini Olish

Ma'lum sana uchun ayirboshlash kurslarini olish va saqlash.

```bash
# Bugungi kurslarni olish
php artisan cbu:fetch-rates

# Ma'lum sana uchun
php artisan cbu:fetch-rates 2025-01-25

# Kechagi kurslarni olish
php artisan cbu:fetch-rates yesterday

# Nisbiy sana uchun
php artisan cbu:fetch-rates "1 week ago"
php artisan cbu:fetch-rates "2025-01-01"
```

**Bu buyruq nima qiladi:**
- Belgilangan sana uchun CBU API dan ayirboshlash kurslarini oladi
- Kurslarni `currency_rates` jadvaliga saqlaydi
- Agar mavjud bo'lsa, mavjud kurslarni yangilaydi

**Natija namunasi:**
```
Fetching rates for date: 2025-01-25
Found 30 rates
Saved: USD (12705.00), EUR (13500.00), RUB (130.00), GBP (16200.00)...
Currency rates fetched successfully.
```

**Qachon ishlatish kerak:**
- Kurslarni yangilab turish uchun kunlik
- Tarixiy kurslarni olish uchun
- Valyutalarni sinxronlashdan keyin

## ⏰ Avtomatik Sinxronizatsiya

Prodakshn muhitda valyuta yangilanishlarini avtomatlashtirish uchun Laravel Task Scheduler'dan foydalaning.

### Laravel Scheduler

`app/Console/Kernel.php` fayliga qo'shing:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Har kuni soat 10:00 da kurslarni olish (CBU yangilaganidan keyin)
        $schedule->command('cbu:fetch-rates')
            ->dailyAt('10:00')
            ->onFailure(function () {
                // Sinxronizatsiya muvaffaqiyatsiz bo'lsa admin xabardor qilish
            });

        // Har dushanba kuni soat 09:00 da valyutalarni sinxronlash
        $schedule->command('cbu:sync-currencies')
            ->weekly()
            ->mondays()
            ->at('09:00');
    }
}
```

### Scheduler'ni Faollashtirish

Laravel scheduler ishlab turishini ta'minlash uchun cron'ga quyidagini qo'shing:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Muqobil: To'g'ridan-to'g'ri Cron Jobs

Agar Laravel Scheduler ishlatmasangiz, cron jobs'ni to'g'ridan-to'g'ri qo'shing:

```bash
# Har kuni soat 10:00 da kurslarni olish
0 10 * * * cd /path-to-your-project && php artisan cbu:fetch-rates

# Har dushanba kuni soat 09:00 da valyutalarni sinxronlash
0 9 * * 1 cd /path-to-your-project && php artisan cbu:sync-currencies
```

### Docker/Kubernetes

Konteynerli muhitlar uchun supervisor yoki shunga o'xshashni ishlating:

```ini
[program:cbu-scheduler]
command=/usr/bin/php /var/www/html/artisan schedule:work
autostart=true
autorestart=true
```

## 🗄️ Ma'lumotlar Bazasi Strukturasi

### Currencies Jadvali

| Ustun | Tur | Tavsif |
|-------|-----|--------|
| id | bigint | Asosiy kalit |
| ccy | string | Valyuta kodi (unikal) - USD, EUR, RUB |
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
| id | bigint | Asosiy kalit |
| currency_id | bigint | Valyutaga tashqi kalit |
| date | date | Kurs sanasi (indekslangan) |
| currency_date | date | CBU asl sanasi |
| rate | decimal(15,4) | Ayirboshlash kursi - 12705.0000 |
| diff | decimal(15,4) | Oldingi kundan farq - 15.2500 |
| nominal | integer | Nominal qiymat - 1 |
| created_at | timestamp | Yaratilgan vaqt |
| updated_at | timestamp | Yangilangan vaqt |

**Indekslar:**
- `date` - Tez qidirish uchun
- `['currency_id', 'date']` - Kompozit indeks

**Unikal cheklov:** `['currency_id', 'date']` - Bir valyuta uchun bir kunda faqat bitta kurs

## 🧪 Testlash

Paket Pest PHP framework yordamida to'liq test qamrovini ta'minlaydi.

### Testlarni Ishga Tushirish

```bash
# Barcha testlar
composer test

# Faqat Unit testlar
vendor/bin/pest --testsuite=Unit

# Faqat Feature testlar
vendor/bin/pest --testsuite=Feature

# Batafsil rejimda
vendor/bin/pest --verbose
```

Batafsil test hujjatlari uchun: [TESTING.md](TESTING.md)

## 🌐 API Endpoints

Paket valyuta ma'lumotlariga kirish uchun avtomatik ravishda RESTful API endpointlarni ro'yxatdan o'tkazadi.

**Asosiy URL:** `{your-domain}/api/cbu`

### 1. Barcha Valyutalar

```http
GET /api/cbu/currencies
```

**Javob:**
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

### 2. Valyuta Kodlari

```http
GET /api/cbu/currencies/codes
```

**Javob:**
```json
{
  "msg": null,
  "error": null,
  "success": true,
  "data": ["USD", "EUR", "RUB", "GBP", "JPY", "CHF"]
}
```

### 3. Ma'lum Valyuta

```http
GET /api/cbu/currencies/{code}
```

**Misol:**
```http
GET /api/cbu/currencies/USD
```

**Javob:**
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

### 4. Bugungi Kurslar

```http
GET /api/cbu/rates/today
```

**Javob:**
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

### 5. Sana Bo'yicha Kurslar

```http
GET /api/cbu/rates?date={date}
```

**Query Parametrlar:**
- `date` (ixtiyoriy): `Y-m-d` formatidagi sana (masalan, `2025-01-25`)

**Misol:**
```http
GET /api/cbu/rates?date=2025-01-25
```

### 6. Ma'lum Valyuta Kursi

```http
GET /api/cbu/rates/{code}?date={date}
```

**Misol:**
```http
GET /api/cbu/rates/USD?date=2025-01-15
```

**Javob:**
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

### 7. Valyuta Konvertatsiyasi

```http
POST /api/cbu/convert
Content-Type: application/json
```

**So'rov tanasi:**
```json
{
  "amount": 100,
  "from": "USD",
  "to": "EUR",
  "date": "2025-01-15"
}
```

**Javob:**
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

**Validatsiya Qoidalari:**
- `amount` - majburiy, raqamli, minimal 0.01
- `from` - majburiy, to'g'ri 3 harfli valyuta kodi
- `to` - majburiy, to'g'ri 3 harfli valyuta kodi
- `date` - ixtiyoriy, Y-m-d formati, kelajak sanasi bo'lmasligi kerak

### 8. Konvertatsiya Kursi

```http
GET /api/cbu/convert/rate/{from}/{to}?date={date}
```

Manba valyutaning 1 birligi uchun konvertatsiya kursini qaytaradi.

**Misol:**
```http
GET /api/cbu/convert/rate/USD/EUR?date=2025-01-15
```

**Javob:**
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

### Xato Javoblari

Barcha endpointlar bir xil xato formatini qaytaradi:

```json
{
  "msg": "Valyuta topilmadi",
  "error": "'XYZ' valyuta kodi mavjud emas",
  "success": false,
  "data": []
}
```

**Umumiy HTTP Status Kodlari:**
- `200` - Muvaffaqiyatli
- `400` - Noto'g'ri so'rov (validatsiya xatosi)
- `404` - Topilmadi (valyuta yoki kurs topilmadi)
- `500` - Ichki server xatosi

### API Routelarni Sozlash

API route prefiksini `config/cbu-currency.php` faylida sozlashingiz mumkin:

```php
'routes' => [
    'prefix' => env('CBU_ROUTES_PREFIX', 'api/cbu'),
    'middleware' => ['api'],
],
```

Yoki `.env` faylida:

```env
CBU_ROUTES_PREFIX=api/v1/currency
```

## 📄 Litsenziya

MIT litsenziyasi. Batafsil ma'lumot uchun [LICENSE](LICENSE) faylini ko'ring.

## 👨‍💻 Muallif

**Nurbek Jummayev**
- GitHub: [@nurbekjummayev](https://github.com/nurbekjummayev)
- Email: jummayevnurbek279@gmail.com

## 🔗 Foydali Havolalar

- [CBU Rasmiy Sayti](https://cbu.uz/)
- [CBU API Hujjatlari](https://cbu.uz/uz/arkhiv-kursov-valyut/veb-masteram/)
- [Laravel Hujjatlari](https://laravel.com/docs)
- [Pest PHP Hujjatlari](https://pestphp.com/docs)

---

<div align="center">
Made with ❤️ in Uzbekistan
</div>
