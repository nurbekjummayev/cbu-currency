<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;

/**
 * Sync Builder - Fluent builder for syncing currency data from API to database
 *
 * Provides a convenient and readable interface for fetching currency rates
 * from the CBU API and storing them in the database.
 */
class SyncBuilder
{
    protected ?string $date = null;

    protected ?array $dates = null;

    protected ?int $lastDays = null;

    protected bool $onlyCurrencies = false;

    public function __construct(
        protected ApiCurrencyRepository $apiRepository,
        protected DatabaseCurrencyRepository $databaseRepository
    ) {}

    /**
     * Set specific date to sync currency rates
     *
     * @param  string  $date  Date in Y-m-d format (e.g., '2025-01-15')
     *
     * @throws CbuApiException
     */
    public function date(string $date): self
    {
        CurrencyHelper::isValidDate($date);
        $this->date = $date;

        return $this;
    }

    /**
     * Set multiple dates to sync currency rates
     *
     * @param  array<string>  $dates  Array of dates in Y-m-d format
     */
    public function dates(array $dates): self
    {
        $this->dates = $dates;

        return $this;
    }

    /**
     * Sync currency rates for the last N days
     *
     * @param  int  $days  Number of days to sync (e.g., 7 for last week)
     */
    public function lastDays(int $days): self
    {
        $this->lastDays = $days;

        return $this;
    }

    /**
     * Sync only currency metadata without rates
     *
     * Updates currency names and codes without saving rate information.
     */
    public function onlyCurrencies(): self
    {
        $this->onlyCurrencies = true;

        return $this;
    }

    /**
     * Execute the sync operation
     *
     * Routes to the appropriate sync method based on configured options:
     * - onlyCurrencies: Syncs only currency metadata
     * - dates: Syncs multiple specific dates
     * - lastDays: Syncs the last N days
     * - default: Syncs single date (today or specified date)
     *
     * @return array{success: bool, message?: string, ...} Sync result with statistics
     *
     * @throws CbuApiException When API request fails
     */
    public function save(): array
    {
        if ($this->onlyCurrencies) {
            return $this->syncCurrencies();
        }

        if ($this->dates !== null) {
            return $this->saveMultipleDates($this->dates);
        }

        if ($this->lastDays !== null) {
            $dates = [];
            for ($i = 0; $i < $this->lastDays; $i++) {
                $dates[] = now()->subDays($i)->format('Y-m-d');
            }

            return $this->saveMultipleDates($dates);
        }

        $date = $this->date ?? now()->format('Y-m-d');

        return $this->saveRatesForDate($date);
    }

    /**
     * Save currency rates for a specific date
     *
     * Fetches rates from API, validates the date, and saves all rates to database.
     * Tracks how many rates were newly created vs updated.
     *
     * @param  string  $date  Date in Y-m-d format
     * @return array{success: bool, message: string, rates_saved: int, rates_updated: int, total_rates: int, date: string}
     *
     * @throws CbuApiException When API request fails or date is invalid
     */
    protected function saveRatesForDate(string $date): array
    {
        CurrencyHelper::logInfo('Starting currency rates sync', ['date' => $date]);

        CurrencyHelper::isValidDate($date);

        $rates = $this->apiRepository->getRatesByDate($date);

        CurrencyHelper::logDebug('Fetched rates from API', [
            'date' => $date,
            'count' => $rates->count(),
        ]);

        $ratesSaved = 0;
        $ratesUpdated = 0;

        foreach ($rates as $rateDto) {
            $currency = $this->databaseRepository->findOrCreateCurrency($rateDto);

            $currencyRate = $this->databaseRepository->saveOrUpdateRate($currency, $rateDto, $date);

            if ($currencyRate->wasRecentlyCreated) {
                $ratesSaved++;
            } else {
                $ratesUpdated++;
            }
        }

        CurrencyHelper::logInfo('Currency rates sync completed', [
            'date' => $date,
            'rates_saved' => $ratesSaved,
            'rates_updated' => $ratesUpdated,
            'total_rates' => $rates->count(),
        ]);

        return [
            'success' => true,
            'message' => 'Currency rates saved successfully',
            'rates_saved' => $ratesSaved,
            'rates_updated' => $ratesUpdated,
            'total_rates' => $rates->count(),
            'date' => $date,
        ];
    }

    /**
     * Sync currency metadata only (without rates)
     *
     * Fetches today's rates from API and updates only currency information
     * (names, codes) without saving rate data. Useful for updating currency list.
     *
     * @return array{success: bool, message: string, currencies_added: int, currencies_updated: int, total_currencies: int}
     */
    protected function syncCurrencies(): array
    {
        CurrencyHelper::logInfo('Starting currencies sync');

        try {
            $date = now()->format('Y-m-d');

            $rates = $this->apiRepository->getRatesByDate($date);

            $currenciesAdded = 0;
            $currenciesUpdated = 0;

            foreach ($rates as $rateDto) {
                $currency = $this->databaseRepository->syncCurrency($rateDto);

                if ($currency->wasRecentlyCreated) {
                    $currenciesAdded++;
                } else {
                    $currenciesUpdated++;
                }
            }

            CurrencyHelper::logInfo('Currencies sync completed', [
                'currencies_added' => $currenciesAdded,
                'currencies_updated' => $currenciesUpdated,
                'total_currencies' => $rates->count(),
            ]);

            return [
                'success' => true,
                'message' => 'Currencies synced successfully',
                'currencies_added' => $currenciesAdded,
                'currencies_updated' => $currenciesUpdated,
                'total_currencies' => $rates->count(),
            ];
        } catch (CbuApiException $e) {
            CurrencyHelper::logError('Currencies sync failed', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'currencies_added' => 0,
                'currencies_updated' => 0,
                'total_currencies' => 0,
            ];
        }
    }

    /**
     * Save currency rates for multiple dates
     *
     * Iterates through the provided dates and syncs rates for each one.
     * Continues on errors, tracking success/failure for each date.
     *
     * @param  array<string>  $dates  Array of dates in Y-m-d format
     * @return array{success: bool, total_dates: int, total_success: int, total_failed: int, results: array<string, array>}
     */
    protected function saveMultipleDates(array $dates): array
    {
        CurrencyHelper::logInfo('Starting batch sync for multiple dates', [
            'dates_count' => count($dates),
            'dates' => $dates,
        ]);

        $results = [];
        $totalSuccess = 0;
        $totalFailed = 0;

        foreach ($dates as $date) {
            try {
                $result = $this->saveRatesForDate($date);
                $results[$date] = $result;
                $totalSuccess++;
            } catch (CbuApiException $e) {
                CurrencyHelper::logError('Failed to sync rates for date', [
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);

                $results[$date] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'date' => $date,
                ];
                $totalFailed++;
            }
        }

        CurrencyHelper::logInfo('Batch sync completed', [
            'total_dates' => count($dates),
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
        ]);

        return [
            'success' => $totalFailed === 0,
            'total_dates' => count($dates),
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'results' => $results,
        ];
    }
}
