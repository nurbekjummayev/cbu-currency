<?php

declare(strict_types=1);

use Cbu\Currency\Builders\ConvertBuilder;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;

function makeRateDto(CurrencyCcy $ccy, CurrencyNumericCode $code, float $rate, int $nominal = 1): CurrencyRateDto
{
    return new CurrencyRateDto(
        cbu_id: 1,
        rate: $rate,
        diff: 0.0,
        nominal: $nominal,
        date: now()->format('Y-m-d'),
        ccy: $ccy,
        code: $code,
        currency_date: now()->format('Y-m-d'),
        name_en: $ccy->value,
        name_uz: $ccy->value,
        name_oz: $ccy->value,
        name_ru: $ccy->value,
    );
}

beforeEach(function () {
    $this->repository = Mockery::mock(CurrencyRepositoryInterface::class);
    $this->builder = new ConvertBuilder($this->repository);
});

describe('precision and rounding', function () {
    test('cross conversion returns the full-precision result without rounding by default', function () {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'RUB')
            ->andReturn(makeRateDto(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61));

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn(makeRateDto(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48));

        // 100 RUB -> 16661 UZS -> / 12014.48 = 1.38674332971547665816...
        $result = $this->builder
            ->from('RUB')
            ->to('USD')
            ->amount(100)
            ->get();

        // Old behavior (intermediate truncation at scale 2) would give 1.38;
        // without rounding the full computed value is returned
        expect($result->result)->toEqualWithDelta(1.3867433297154767, 1e-12);
        expect($result->amountInUzs)->toBe(16661.0);
    });

    test('result DTO can be rounded afterwards with round()', function () {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'RUB')
            ->andReturn(makeRateDto(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61));

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn(makeRateDto(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48));

        $result = $this->builder
            ->from('RUB')
            ->to('USD')
            ->amount(100)
            ->get()
            ->round(2);

        expect($result->result)->toBe(1.39)
            ->and($result->amountInUzs)->toBe(16661.0)
            ->and($result->fromRate)->toBe(166.61);
    });

    test('scale() rounds the final result half-up to the requested decimals', function () {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'RUB')
            ->andReturn(makeRateDto(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61));

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn(makeRateDto(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48));

        $result = $this->builder
            ->from('RUB')
            ->to('USD')
            ->amount(100)
            ->scale(2)
            ->get();

        // 1.38674... rounds half-up to 1.39 (plain bcmath truncation would give 1.38)
        expect($result->result)->toBe(1.39);
    });
});

describe('nominal handling', function () {
    test('divides the rate by nominal when converting to UZS', function () {
        // CBU quotes VND per 10 units: Rate=4.56, Nominal=10 -> 1 VND = 0.456 UZS
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'VND')
            ->andReturn(makeRateDto(CurrencyCcy::VND, CurrencyNumericCode::VND, 4.56, nominal: 10));

        $result = $this->builder
            ->from('VND')
            ->to('UZS')
            ->amount(100)
            ->get();

        // Without nominal handling this would wrongly be 456.0
        expect($result->result)->toBe(45.6);
        expect($result->fromRate)->toBe(0.456);
    });

    test('divides the rate by nominal when converting from UZS', function () {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), 'VND')
            ->andReturn(makeRateDto(CurrencyCcy::VND, CurrencyNumericCode::VND, 4.56, nominal: 10));

        $result = $this->builder
            ->from('UZS')
            ->to('VND')
            ->amount(45.6)
            ->get();

        expect($result->result)->toBe(100.0);
        expect($result->toRate)->toBe(0.456);
    });
});

describe('CurrencyHelper::bcRound()', function () {
    test('rounds half-up at the requested scale', function () {
        expect(CurrencyHelper::bcRound('1.005', 2))->toBe('1.01')
            ->and(CurrencyHelper::bcRound('1.004', 2))->toBe('1.00')
            ->and(CurrencyHelper::bcRound('1.38674332971547665816', 10))->toBe('1.3867433297')
            ->and(CurrencyHelper::bcRound('1.38674332999', 10))->toBe('1.3867433300');
    });

    test('rounds negative numbers away from zero', function () {
        expect(CurrencyHelper::bcRound('-1.005', 2))->toBe('-1.01')
            ->and(CurrencyHelper::bcRound('-1.004', 2))->toBe('-1.00');
    });

    test('handles integers without a decimal point', function () {
        expect(CurrencyHelper::bcRound('5', 2))->toBe('5.00');
    });
});
