<?php

declare(strict_types=1);

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Models\Currency;
use Cbu\Currency\Models\CurrencyRate;

function seedUsdRate(string $date, float $rate = 12014.48): void
{
    $currency = Currency::query()->create([
        'ccy' => CurrencyCcy::USD,
        'code' => CurrencyNumericCode::USD,
        'cbu_id' => '69',
        'name_uz' => 'AQSH dollari',
        'name_oz' => 'АҚШ доллари',
        'name_ru' => 'Доллар США',
        'name_en' => 'US Dollar',
    ]);

    CurrencyRate::query()->create([
        'currency_id' => $currency->id,
        'date' => $date,
        'currency_date' => $date,
        'rate' => $rate,
        'diff' => 0,
        'nominal' => 1,
    ]);
}

describe('POST /api/cbu/convert - scale parameter', function () {
    test('returns the full-precision result when scale is not sent', function () {
        $date = now()->format('Y-m-d');
        seedUsdRate($date);

        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'UZS',
            'to' => 'USD',
            'date' => $date,
        ]);

        // 100 / 12014.48 = 0.0083232897... — returned unrounded
        $response->assertOk();
        expect($response->json('data.result'))
            ->toEqualWithDelta(100 / 12014.48, 1e-12);
    });

    test('rounds the final result when scale is sent', function () {
        $date = now()->format('Y-m-d');
        seedUsdRate($date);

        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'UZS',
            'to' => 'USD',
            'date' => $date,
            'scale' => 4,
        ]);

        $response->assertOk();
        expect($response->json('data.result'))->toBe(round(100 / 12014.48, 4));
    });

    test('fails when scale is invalid', function () {
        $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'scale' => 'abc',
        ])->assertStatus(422)->assertJsonValidationErrors(['scale']);

        $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'scale' => -1,
        ])->assertStatus(422)->assertJsonValidationErrors(['scale']);
    });
});

describe('GET /api/cbu/convert/rate - scale parameter', function () {
    test('rounds the rate result when scale is sent', function () {
        $date = now()->format('Y-m-d');
        seedUsdRate($date);

        $response = $this->getJson("/api/cbu/convert/rate/UZS/USD?date={$date}&scale=6");

        $response->assertOk();
        expect($response->json('data.result'))->toBe(round(1 / 12014.48, 6));
    });

    test('fails when scale is invalid', function () {
        $this->getJson('/api/cbu/convert/rate/USD/UZS?scale=99')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['scale']);
    });
});

describe('POST /api/cbu/convert - Validation Tests', function () {
    test('fails when amount is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('fails when amount is zero or negative', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 0,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        $response = $this->postJson('/api/cbu/convert', [
            'amount' => -10,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('fails when from currency is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    });

    test('fails when to currency is missing', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    });

    test('fails when currency code is invalid', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'INVALID',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from']);
    });

    test('fails when date format is invalid', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => '2025/01/15',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('fails when date is in the future', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('validates numeric amount', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 'not-a-number',
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('validates minimum amount', function () {
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 0.001,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('accepts valid currency conversion request structure', function () {
        // This test just validates the request structure passes validation
        // It won't test actual conversion since that requires API/DB setup
        $response = $this->postJson('/api/cbu/convert', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => now()->format('Y-m-d'),
        ]);

        // We expect either 200 (success) or 500 (API error)
        // But NOT 422 (validation error)
        expect($response->status())->not->toBe(422);
    });
});

describe('GET /api/cbu/convert/rate/{from}/{to} - Route Tests', function () {
    test('route exists and accepts valid currency codes', function () {
        $response = $this->getJson('/api/cbu/convert/rate/USD/EUR');

        // Should not return 404 (route exists)
        expect($response->status())->not->toBe(404);
    });

    test('route requires uppercase currency codes', function () {
        $response = $this->getJson('/api/cbu/convert/rate/usd/eur');

        // Lowercase should return 404 (route requires uppercase)
        expect($response->status())->toBe(404);
    });

    test('accepts date query parameter', function () {
        $response = $this->getJson('/api/cbu/convert/rate/USD/EUR?date='.now()->format('Y-m-d'));

        // Should not return 404
        expect($response->status())->not->toBe(404);
    });
});
