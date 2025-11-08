<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Enums\CurrencySource;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RatesBuilder
{
    protected ?string $date = null;

    protected ?CurrencyCcy $ccy = null;

    protected ?CurrencyNumericCode $numericCode = null;

    protected ?int $cacheDuration = null;

    public function __construct(
        protected CurrencyRepositoryInterface $repository
    ) {
        $this->cacheDuration = config('cbu-currency.cache_duration');
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Set the data source (API or Database)
     */
    public function source(CurrencySource $source): self
    {
        $this->repository = match ($source) {
            CurrencySource::API => app(ApiCurrencyRepository::class),
            CurrencySource::DATABASE => app(DatabaseCurrencyRepository::class),
        };

        return $this;
    }

    /**
     * Set the date for fetching rates
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
     * Set the currency code (CCY)
     */
    public function ccy(CurrencyCcy|string $ccy): self
    {
        $this->ccy = $ccy instanceof CurrencyCcy ? $ccy : CurrencyCcy::from(strtoupper($ccy));

        return $this;
    }

    /**rates
     * Set the currency by numeric code
     */
    public function code(CurrencyNumericCode|int $numericCode): self
    {
        $this->numericCode = $numericCode instanceof CurrencyNumericCode ? $numericCode : CurrencyNumericCode::from($numericCode);

        return $this;
    }

    /**
     * Enable caching for this query
     *
     * @param  int|null  $minutes  Cache duration in minutes (null = use config default)
     */
    public function cache(?int $minutes = null): self
    {
        $this->cacheDuration = $minutes;

        return $this;
    }

    /**
     * Execute and get the result
     *
     * @return CurrencyRateDto|Collection<CurrencyRateDto>
     *
     * @throws CbuApiException
     */
    public function get(): CurrencyRateDto|Collection
    {
        if ($this->cacheDuration !== null and $this->cacheDuration > 0) {
            $cacheKey = $this->getCacheKey();

            return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () {
                return $this->fetchRates();
            });
        }

        return $this->fetchRates();
    }

    /**
     * Fetch rates from repository (without cache)
     *
     * @return CurrencyRateDto|Collection<CurrencyRateDto>
     *
     * @throws CbuApiException
     */
    protected function fetchRates(): CurrencyRateDto|Collection
    {
        $date = $this->date;

        if (! $this->ccy && ! $this->numericCode) {
            return $this->repository->getRatesByDate($date);
        }

        if ($this->ccy) {
            return $this->repository->getRateByCcy($date, $this->ccy);
        }

        if ($this->numericCode) {
            return $this->repository->getRateByNumericCode($date, $this->numericCode);
        }

        throw CbuApiException::invalidQueryParameters('No currency specified. Use ccy() or code() method');
    }

    /**
     * Generate cache key based on query parameters
     */
    protected function getCacheKey(): string
    {
        $date = $this->date ?? now()->format('Y-m-d');
        $parts = [
            'cbu_currency_rate',
            $date,
            $this->ccy->value ?? 'all',
            $this->numericCode->value ?? 'no_code',
            get_class($this->repository),
        ];

        return implode('_', $parts);
    }

    /**
     * Get all rates for the specified or current date
     *
     * @return Collection<CurrencyRateDto>
     */
    public function all(): Collection
    {
        $date = $this->date;
        if ($this->cacheDuration !== null and $this->cacheDuration > 0) {
            $cacheKey = 'cbu_currency_rates_all_'.$date.'_'.get_class($this->repository);

            return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($date) {
                return $this->repository->getRatesByDate($date);
            });
        }

        return $this->repository->getRatesByDate($date);
    }

    /**
     * Alias for get() - returns first result or collection
     *
     * @return CurrencyRateDto|Collection<CurrencyRateDto>
     *
     * @throws CbuApiException
     */
    public function first(): CurrencyRateDto|Collection
    {
        return $this->get();
    }
}
