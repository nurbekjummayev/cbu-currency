<?php

declare(strict_types=1);

use Cbu\Currency\Builders\ConvertBuilder;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;

beforeEach(function () {
    // Mock repository
    $this->repository = Mockery::mock(CurrencyRepositoryInterface::class);
    $this->builder = new ConvertBuilder($this->repository);
});

describe('ConvertBuilder validation', function () {
    test('throws exception when from currency is missing', function () {
        expect(fn() => $this->builder
            ->to('UZS')
            ->amount(100)
            ->get())
            ->toThrow(CbuApiException::class);
    });

    test('throws exception when to currency is missing', function () {
        expect(fn() => $this->builder
            ->from('USD')
            ->amount(100)
            ->get())
            ->toThrow(CbuApiException::class);
    });

    test('throws exception when amount is missing', function () {
        expect(fn() => $this->builder
            ->from('USD')
            ->to('UZS')
            ->get())
            ->toThrow(CbuApiException::class);
    });

    test('throws exception when amount is zero', function () {
        expect(fn() => $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(0)
            ->get())
            ->toThrow(CbuApiException::class);
    });

    test('throws exception when amount is negative', function () {
        expect(fn() => $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(-100)
            ->get())
            ->toThrow(CbuApiException::class);
    });

    test('throws exception for invalid date format', function () {
        expect(fn() => $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(100)
            ->date('2025/01/15')
            ->get())
            ->toThrow(CbuApiException::class);
    });
});

describe('ConvertBuilder conversion calculations with mocked data', function () {
    test('converts foreign currency to UZS', function () {
        // Mock getRateByCcy to return USD rate
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->once()
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(100)
            ->get();

        expect($result->amount)->toBe(100.0);
        expect($result->fromCurrency)->toBe('USD');
        expect($result->toCurrency)->toBe('UZS');
        expect($result->result)->toBe(1270500.0);
        expect($result->fromRate)->toBe(12705.0);
        expect($result->toRate)->toBe(1.0);
    });

    test('converts UZS to foreign currency', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->once()
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('UZS')
            ->to('USD')
            ->amount(127050)
            ->get();

        expect($result->amount)->toBe(127050.0);
        expect($result->fromCurrency)->toBe('UZS');
        expect($result->toCurrency)->toBe('USD');
        expect($result->result)->toBe(10.0);
    });

    test('converts between two foreign currencies', function () {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->once()
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn(new CurrencyRateDto(
                cbu_id: 1,
                rate: 12705.0,
                diff: 0.0,
                nominal: 1,
                date: now()->format('Y-m-d'),
                ccy: CurrencyCcy::USD,
                code: CurrencyNumericCode::USD,
                currency_date: now()->format('Y-m-d'),
                name_en: 'US Dollar',
                name_uz: 'AQSH dollari',
                name_oz: 'АҚШ доллари',
                name_ru: 'Доллар США'
            ));

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->once()
            ->with(now()->format('Y-m-d'), 'EUR')
            ->andReturn(new CurrencyRateDto(
                cbu_id: 2,
                rate: 13500.0,
                diff: 0.0,
                nominal: 1,
                date: now()->format('Y-m-d'),
                ccy: CurrencyCcy::EUR,
                code: CurrencyNumericCode::EUR,
                currency_date: now()->format('Y-m-d'),
                name_en: 'Euro',
                name_uz: 'Yevro',
                name_oz: 'Евро',
                name_ru: 'Евро'
            ));

        $result = $this->builder
            ->from('USD')
            ->to('EUR')
            ->amount(100)
            ->get();

        expect($result->amount)->toBe(100.0);
        expect($result->fromCurrency)->toBe('USD');
        expect($result->toCurrency)->toBe('EUR');
        // 100 USD = 1270500 UZS, 1270500 / 13500 = 94.11 EUR
        expect($result->result)->toBeGreaterThan(94.0);
        expect($result->result)->toBeLessThan(95.0);
    });

    test('converts same currency', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->once()
            ->with(now()->format('Y-m-d'), 'USD')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('USD')
            ->amount(100)
            ->get();

        expect($result->amount)->toBe(100.0);
        expect($result->result)->toBe(100.0);
    });

    test('UZS to UZS conversion', function () {
        $result = $this->builder
            ->from('UZS')
            ->to('UZS')
            ->amount(1000)
            ->get();

        expect($result->amount)->toBe(1000.0);
        expect($result->result)->toBe(1000.0);
        expect($result->fromRate)->toBeNull();
        expect($result->toRate)->toBeNull();
    });
});

describe('ConvertBuilder fluent interface', function () {
    test('from() and to() set currencies', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(100)
            ->get();

        expect($result->fromCurrency)->toBe('USD');
        expect($result->toCurrency)->toBe('UZS');
    });

    test('amount() sets conversion amount', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(250.50)
            ->get();

        expect($result->amount)->toBe(250.50);
    });
});

describe('ConvertBuilder precision', function () {
    test('handles decimal amounts correctly', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(99.99)
            ->get();

        expect($result->amount)->toBe(99.99);
        // 99.99 * 12705 = 1270372.95
        expect($result->result)->toBe(1270372.95);
    });

    test('uses bcmath for small amounts', function () {
        $mockDto = new CurrencyRateDto(
            cbu_id: 1,
            rate: 12705.0,
            diff: 0.0,
            nominal: 1,
            date: now()->format('Y-m-d'),
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: now()->format('Y-m-d'),
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США'
        );

        $this->repository
            ->shouldReceive('getRateByCcy')
            ->andReturn($mockDto);

        $result = $this->builder
            ->from('USD')
            ->to('UZS')
            ->amount(0.01)
            ->get();

        expect($result->result)->toBe(127.05);
    });
});
