<?php

declare(strict_types=1);

use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Models\Currency;
use Cbu\Currency\Models\CurrencyRate;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The database already has the data — no API fallback should ever fire
    Http::preventStrayRequests();

    $this->currency = Currency::query()->create([
        'ccy' => CurrencyCcy::USD,
        'code' => CurrencyNumericCode::USD,
        'cbu_id' => '69',
        'name_uz' => 'AQSH dollari',
        'name_oz' => 'АҚШ доллари',
        'name_ru' => 'Доллар США',
        'name_en' => 'US Dollar',
    ]);

    $this->rate = CurrencyRate::query()->create([
        'currency_id' => $this->currency->id,
        'date' => '2026-01-15',
        'currency_date' => '2026-01-15',
        'rate' => 12014.48,
        'diff' => -39.55,
        'nominal' => 1,
    ]);
});

describe('DatabaseCurrencyRepository', function () {
    test('getRateByCcy maps the model to a DTO with correct types', function () {
        $repository = app(DatabaseCurrencyRepository::class);

        $dto = $repository->getRateByCcy('2026-01-15', 'USD');

        expect($dto)->toBeInstanceOf(CurrencyRateDto::class)
            ->and($dto->rate)->toBe(12014.48)
            ->and($dto->diff)->toBe(-39.55)
            ->and($dto->nominal)->toBe(1)
            ->and($dto->date)->toBe('2026-01-15')
            ->and($dto->currency_date)->toBe('2026-01-15')
            ->and($dto->ccy)->toBe(CurrencyCcy::USD)
            ->and($dto->code)->toBe(CurrencyNumericCode::USD)
            ->and($dto->cbu_id)->toBe(69);
    });

    test('getRatesByDate maps models to DTOs', function () {
        $repository = app(DatabaseCurrencyRepository::class);

        $rates = $repository->getRatesByDate('2026-01-15');

        expect($rates)->toHaveCount(1)
            ->and($rates->first())->toBeInstanceOf(CurrencyRateDto::class)
            ->and($rates->first()->rate)->toBe(12014.48);
    });

    test('saveOrUpdateRate updates the existing rate instead of duplicating it', function () {
        $repository = app(DatabaseCurrencyRepository::class);

        $dto = new CurrencyRateDto(
            cbu_id: 69,
            rate: 12100.55,
            diff: 86.07,
            nominal: 1,
            date: '2026-01-15',
            ccy: CurrencyCcy::USD,
            code: CurrencyNumericCode::USD,
            currency_date: '2026-01-15',
            name_en: 'US Dollar',
            name_uz: 'AQSH dollari',
            name_oz: 'АҚШ доллари',
            name_ru: 'Доллар США',
        );

        // Same currency + date as the seeded rate — must update, not insert
        $rate = $repository->saveOrUpdateRate($this->currency, $dto, '2026-01-15');

        expect(CurrencyRate::query()->count())->toBe(1)
            ->and((float) $rate->rate)->toBe(12100.55);
    });

    test('findOrCreateCurrency stores the numeric code and cbu id correctly', function () {
        $repository = app(DatabaseCurrencyRepository::class);

        $dto = new CurrencyRateDto(
            cbu_id: 21,
            rate: 13858.70,
            diff: -73.35,
            nominal: 1,
            date: '2026-01-15',
            ccy: CurrencyCcy::EUR,
            code: CurrencyNumericCode::EUR,
            currency_date: '2026-01-15',
            name_en: 'Euro',
            name_uz: 'Yevro',
            name_oz: 'Евро',
            name_ru: 'Евро',
        );

        $currency = $repository->findOrCreateCurrency($dto);

        expect($currency->ccy)->toBe(CurrencyCcy::EUR)
            ->and($currency->code)->toBe(CurrencyNumericCode::EUR)
            ->and((string) $currency->cbu_id)->toBe('21');
    });
});
