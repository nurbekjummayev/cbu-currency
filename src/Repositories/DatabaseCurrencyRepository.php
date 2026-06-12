<?php

declare(strict_types=1);

namespace Cbu\Currency\Repositories;

use Cbu\Currency\DTOs\CurrencyDto;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Models\Currency;
use Cbu\Currency\Models\CurrencyRate;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Database Currency Repository
 *
 * Handles currency rate retrieval from database with automatic API fallback.
 * When data is not found in the database, it fetches from the CBU API and caches it.
 */
class DatabaseCurrencyRepository implements CurrencyRepositoryInterface
{
    public function __construct(
        protected ApiCurrencyRepository $apiRepository
    ) {}

    /**
     * Get all currency rates for a specific date
     *
     * Attempts to retrieve from database first. If not found, fetches from API,
     * saves to database, and returns the fresh data.
     *
     * @param  string  $date  Date in Y-m-d format (e.g., '2025-01-15')
     * @return Collection<CurrencyRateDto> Collection of currency rate DTOs
     *
     * @throws CbuApiException When API request fails
     * @throws Throwable When database transaction fails
     */
    public function getRatesByDate(string $date): Collection
    {
        $rates = CurrencyRate::query()
            ->with('currency')
            ->whereDate('date', $date)
            ->get();

        // If no data in database, fetch from API and cache it
        if ($rates->isEmpty()) {
            $ratesFromApi = $this->apiRepository->getRatesByDate($date);

            // Save all rates in a single transaction to ensure data consistency
            DB::transaction(function () use ($ratesFromApi, $date) {
                foreach ($ratesFromApi as $rateDto) {
                    $currency = $this->findOrCreateCurrency($rateDto);
                    $this->saveOrUpdateRate($currency, $rateDto, $date);
                }
            });

            return $ratesFromApi;
        }

        return $rates->map(fn (CurrencyRate $rate) => CurrencyRateDto::setDataFromModel($rate));
    }

    /**
     * Get today's currency rate by currency code (e.g., 'USD', 'EUR')
     *
     * @param  CurrencyCcy|string  $currencyCode  Currency code enum or string
     * @return CurrencyRateDto Currency rate DTO
     *
     * @throws CbuApiException When API request fails
     */
    public function getRateTodayCcy(CurrencyCcy|string $currencyCode): CurrencyRateDto
    {
        $today = now()->format('Y-m-d');

        return $this->getRateByCcy($today, $currencyCode);
    }

    /**
     * Get today's currency rate by numeric code (e.g., 840 for USD)
     *
     * @param  int|CurrencyNumericCode  $currencyCode  Numeric currency code
     * @return CurrencyRateDto Currency rate DTO
     *
     * @throws CbuApiException When API request fails
     */
    public function getTodayRateByNumericCode(int|CurrencyNumericCode $currencyCode): CurrencyRateDto
    {
        $today = now()->format('Y-m-d');

        return $this->getRateByNumericCode($today, $currencyCode);
    }

    /**
     * Get currency rate for a specific date and currency code
     *
     * Attempts to retrieve from database first. If not found, fetches from API,
     * saves to database, and returns the fresh data.
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  CurrencyCcy|string  $currencyCode  Currency code (USD, EUR, etc.)
     * @return CurrencyRateDto Currency rate DTO
     *
     * @throws CbuApiException When API request fails
     */
    public function getRateByCcy(string $date, CurrencyCcy|string $currencyCode): CurrencyRateDto
    {
        $ccy = $currencyCode instanceof CurrencyCcy ? $currencyCode : CurrencyCcy::from($currencyCode);

        $rate = CurrencyRate::query()
            ->with('currency')
            ->whereHas('currency', fn ($query) => $query->where('ccy', $ccy))
            ->whereDate('date', $date)
            ->first();

        // If not found in database, fetch from API and cache it
        if (! $rate) {
            $rateDto = $this->apiRepository->getRateByCcy($date, $ccy);
            $currency = $this->findOrCreateCurrency($rateDto);
            $this->saveOrUpdateRate($currency, $rateDto, $date);

            return $rateDto;
        }

        return CurrencyRateDto::setDataFromModel($rate);
    }

    /**
     * Get currency rate by numeric code
     *
     * @param  string  $date  Date in Y-m-d format
     * @param  int|CurrencyNumericCode  $currencyCode  Numeric currency code (840, 978, etc.)
     * @return CurrencyRateDto Currency rate DTO
     *
     * @throws CbuApiException When API request fails
     */
    public function getRateByNumericCode(string $date, int|CurrencyNumericCode $currencyCode): CurrencyRateDto
    {
        $numericCode = $currencyCode instanceof CurrencyNumericCode ? $currencyCode : CurrencyNumericCode::from($currencyCode);

        return $this->getRateByCcy($date, $numericCode->name);
    }

    /**
     * Find existing currency or create a new one
     *
     * Uses firstOrCreate to avoid duplicate entries. If currency exists by code,
     * returns it; otherwise creates a new currency with provided data.
     *
     * @param  CurrencyRateDto  $rateDto  Rate DTO containing currency information
     * @return Currency Currency model instance
     */
    public function findOrCreateCurrency(CurrencyRateDto $rateDto): Currency
    {
        return Currency::query()->firstOrCreate(
            ['ccy' => $rateDto->ccy],
            [
                'code' => $rateDto->code,
                'cbu_id' => $rateDto->cbu_id,
                'name_uz' => $rateDto->name_uz,
                'name_oz' => $rateDto->name_oz,
                'name_ru' => $rateDto->name_ru,
                'name_en' => $rateDto->name_en,
            ]
        );
    }

    /**
     * Save or update currency rate
     *
     * Uses updateOrCreate with composite key (currency_id + date) to prevent duplicates.
     * If a rate exists for this currency on this date, it updates; otherwise creates new.
     *
     * @param  Currency  $currency  Currency model instance
     * @param  CurrencyRateDto  $rateDto  Rate data to save
     * @param  string  $date  Date in Y-m-d format
     * @return CurrencyRate Saved or updated currency rate model
     */
    public function saveOrUpdateRate(Currency $currency, CurrencyRateDto $rateDto, string $date): CurrencyRate
    {
        // updateOrCreate cannot be used here: its lookup compares the raw
        // string against the stored datetime value, which never matches on
        // drivers like SQLite — whereDate handles the comparison correctly.
        $rate = CurrencyRate::query()
            ->where('currency_id', $currency->id)
            ->whereDate('date', $date)
            ->first();

        $values = [
            'rate' => $rateDto->rate,
            'currency_date' => $rateDto->currency_date,
            'diff' => $rateDto->diff,
            'nominal' => $rateDto->nominal,
        ];

        if ($rate) {
            $rate->update($values);

            return $rate;
        }

        return CurrencyRate::query()->create($values + [
            'currency_id' => $currency->id,
            'date' => $date,
        ]);
    }

    /**
     * Sync currency metadata (update or create)
     *
     * Updates all currency fields including multilingual names.
     * Used primarily for syncing currency information without rate data.
     *
     * @param  CurrencyRateDto  $rateDto  Rate DTO containing currency metadata
     * @return Currency Updated or created currency model
     */
    public function syncCurrency(CurrencyRateDto $rateDto): Currency
    {
        return Currency::query()->updateOrCreate(
            ['ccy' => $rateDto->ccy],
            [
                'ccy' => $rateDto->ccy,
                'code' => $rateDto->code,
                'cbu_id' => $rateDto->cbu_id,
                'name_uz' => $rateDto->name_uz,
                'name_oz' => $rateDto->name_oz,
                'name_ru' => $rateDto->name_ru,
                'name_en' => $rateDto->name_en,
            ]
        );
    }

    /**
     * Get all currencies from database
     *
     * Returns all currencies ordered by currency code.
     *
     * @return Collection<CurrencyDto> Collection of currency DTOs
     */
    public function getAllCurrencies(): Collection
    {
        return Currency::query()
            ->orderBy('ccy')
            ->get()
            ->map(fn (Currency $currency) => CurrencyDto::setDataFromModel($currency));
    }

    /**
     * Get specific currency by code
     *
     * Returns a single currency by its code.
     *
     * @param  string  $code  Currency code (e.g., USD, EUR)
     * @return CurrencyDto|null Currency DTO or null if not found
     */
    public function getCurrencyByCode(string $code): ?CurrencyDto
    {
        $currency = Currency::query()
            ->where('ccy', strtoupper($code))
            ->first();

        return $currency ? CurrencyDto::setDataFromModel($currency) : null;
    }
}
