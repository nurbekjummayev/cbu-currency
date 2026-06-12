<?php

declare(strict_types=1);

use Cbu\Currency\Builders\ConvertBuilder;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;

function edgeRateDto(CurrencyCcy $ccy, CurrencyNumericCode $code, float $rate, int $nominal = 1): CurrencyRateDto
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

    $this->mockRate = function (CurrencyCcy $ccy, CurrencyNumericCode $code, float $rate, int $nominal = 1) {
        $this->repository
            ->shouldReceive('getRateByCcy')
            ->with(now()->format('Y-m-d'), $ccy->value)
            ->andReturn(edgeRateDto($ccy, $code, $rate, $nominal));
    };
});

describe('floating point pitfalls', function () {
    test('handles classic float artifact amounts (0.1 + 0.2)', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        // 0.1 + 0.2 = 0.30000000000000004 in binary floating point
        $result = $this->builder->from('USD')->to('UZS')->amount(0.1 + 0.2)->get();

        // The binary noise must not leak into the calculation:
        // 0.3 * 12014.48 = 3604.344
        expect($result->result)->toEqualWithDelta(3604.344, 1e-9);
    });

    test('handles amounts that become scientific notation when cast to string', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        // (string) 0.00001 === '1.0E-5' — must not break BCMath
        $result = $this->builder->from('USD')->to('UZS')->amount(0.00001)->get();

        expect($result->result)->toEqualWithDelta(0.1201448, 1e-12);
    });

    test('handles very large amounts without crashing or losing magnitude', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        // Trillions — near the limit of exact float integers
        $result = $this->builder->from('USD')->to('UZS')->amount(999_999_999_999.99)->get();

        // ~1.2e16 — allow a few ULPs of float error
        expect($result->result)->toEqualWithDelta(999_999_999_999.99 * 12014.48, 10.0);
    });

    test('handles the smallest accepted amount (0.01)', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        $result = $this->builder->from('USD')->to('UZS')->amount(0.01)->get();

        expect($result->result)->toEqualWithDelta(120.1448, 1e-12);
    });
});

describe('banking consistency invariants', function () {
    test('round-trip conversion returns the original amount', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        // 100 USD -> UZS
        $uzs = $this->builder->from('USD')->to('UZS')->amount(100)->get()->result;

        // UZS -> USD must give back exactly what we started with
        $builder = new ConvertBuilder($this->repository);
        $usd = $builder->from('UZS')->to('USD')->amount($uzs)->get()->result;

        expect($usd)->toEqualWithDelta(100.0, 1e-9);
    });

    test('cross rates are reciprocal (RUB->USD times USD->RUB equals 1)', function () {
        ($this->mockRate)(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61);
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        $rubToUsd = $this->builder->from('RUB')->to('USD')->amount(1)->get()->result;

        $builder = new ConvertBuilder($this->repository);
        $usdToRub = $builder->from('USD')->to('RUB')->amount(1)->get()->result;

        expect($rubToUsd * $usdToRub)->toEqualWithDelta(1.0, 1e-12);
    });

    test('cross conversion is internally consistent: result * toRate equals amountInUzs', function () {
        ($this->mockRate)(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61);
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        $result = $this->builder->from('RUB')->to('USD')->amount(100)->get();

        expect($result->result * $result->toRate)
            ->toEqualWithDelta($result->amountInUzs, 1e-6);
    });

    test('converting via nominal currency both directions is consistent', function () {
        // VND quoted per 10 units
        ($this->mockRate)(CurrencyCcy::VND, CurrencyNumericCode::VND, 4.56, nominal: 10);

        $uzs = $this->builder->from('VND')->to('UZS')->amount(1_000_000)->get()->result;

        $builder = new ConvertBuilder($this->repository);
        $vnd = $builder->from('UZS')->to('VND')->amount($uzs)->get()->result;

        expect($vnd)->toEqualWithDelta(1_000_000.0, 1e-6);
    });

    test('builder scale() and DTO round() agree on half-up rounding', function () {
        ($this->mockRate)(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61);
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        $viaScale = $this->builder->from('RUB')->to('USD')->amount(100)->scale(2)->get()->result;

        $builder = new ConvertBuilder($this->repository);
        $viaRound = $builder->from('RUB')->to('USD')->amount(100)->get()->round(2)->result;

        expect($viaScale)->toBe($viaRound)->toBe(1.39);
    });

    test('rounding is idempotent', function () {
        ($this->mockRate)(CurrencyCcy::RUB, CurrencyNumericCode::RUB, 166.61);
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48);

        $result = $this->builder->from('RUB')->to('USD')->amount(100)->get()->round(2);

        expect($result->round(2)->result)->toBe($result->result);
    });
});

describe('bad data from upstream', function () {
    test('zero rate throws a domain exception instead of DivisionByZeroError', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 0.0);

        expect(fn () => $this->builder->from('UZS')->to('USD')->amount(100)->get())
            ->toThrow(CbuApiException::class);
    });

    test('negative rate throws a domain exception', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, -12014.48);

        expect(fn () => $this->builder->from('USD')->to('UZS')->amount(100)->get())
            ->toThrow(CbuApiException::class);
    });

    test('zero nominal is treated as 1 instead of dividing by zero', function () {
        ($this->mockRate)(CurrencyCcy::USD, CurrencyNumericCode::USD, 12014.48, nominal: 0);

        $result = $this->builder->from('USD')->to('UZS')->amount(100)->get();

        expect($result->result)->toEqualWithDelta(1_201_448.0, 1e-9);
    });
});

describe('invalid amounts', function () {
    test('rejects INF amount', function () {
        expect(fn () => $this->builder->from('USD')->to('UZS')->amount(INF)->get())
            ->toThrow(CbuApiException::class);
    });

    test('rejects NAN amount', function () {
        expect(fn () => $this->builder->from('USD')->to('UZS')->amount(NAN)->get())
            ->toThrow(CbuApiException::class);
    });
});

describe('half-up rounding boundaries', function () {
    test('bcRound rounds exact .5 boundaries up, not to even (no banker rounding)', function () {
        expect(CurrencyHelper::bcRound('0.125', 2))->toBe('0.13')
            ->and(CurrencyHelper::bcRound('0.135', 2))->toBe('0.14')
            ->and(CurrencyHelper::bcRound('2.5', 0))->toBe('3')
            ->and(CurrencyHelper::bcRound('-2.5', 0))->toBe('-3');
    });

    test('bcRound is not confused by float-artifact strings', function () {
        // String math is exact — '2.675' rounds to '2.68'
        // (naive float round of 2.675 can give 2.67 because the float is 2.67499...)
        expect(CurrencyHelper::bcRound('2.675', 2))->toBe('2.68');
    });

    test('bcRound handles values with no integer part and many decimals', function () {
        expect(CurrencyHelper::bcRound('0.00000000005', 10))->toBe('0.0000000001')
            ->and(CurrencyHelper::bcRound('0.00000000004', 10))->toBe('0.0000000000');
    });
});
