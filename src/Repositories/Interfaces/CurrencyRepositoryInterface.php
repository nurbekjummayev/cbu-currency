<?php

declare(strict_types=1);

namespace Cbu\Currency\Repositories\Interfaces;

use Cbu\Currency\DTOs\CurrencyDto;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Illuminate\Support\Collection;

interface CurrencyRepositoryInterface
{
    public function getRatesByDate(string $date): Collection;

    public function getRateTodayCcy(CurrencyCcy|string $currencyCode): CurrencyRateDto;

    public function getTodayRateByNumericCode(CurrencyNumericCode|int $currencyCode): CurrencyRateDto;

    public function getRateByCcy(string $date, CurrencyCcy|string $currencyCode): CurrencyRateDto;

    public function getRateByNumericCode(string $date, CurrencyNumericCode|int $currencyCode): CurrencyRateDto;

    public function getAllCurrencies(): Collection;

    public function getCurrencyByCode(string $code): ?CurrencyDto;
}
