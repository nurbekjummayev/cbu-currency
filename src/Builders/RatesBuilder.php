<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\Builders\Concerns\HandlesCaching;
use Cbu\Currency\Builders\Concerns\ResolvesSource;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

class RatesBuilder
{
    use HandlesCaching;
    use ResolvesSource;

    protected string $date;

    protected ?CurrencyCcy $ccy = null;

    protected ?CurrencyNumericCode $numericCode = null;

    public function __construct(
        protected CurrencyRepositoryInterface $repository
    ) {
        $this->cacheDuration = config('cbu-currency.cache_duration');
        $this->date = now()->format('Y-m-d');
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

    /**
     * Set the currency by numeric code
     */
    public function code(CurrencyNumericCode|int $numericCode): self
    {
        $this->numericCode = $numericCode instanceof CurrencyNumericCode ? $numericCode : CurrencyNumericCode::from($numericCode);

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
        return $this->remember($this->getCacheKey(), fn () => $this->fetchRates());
    }

    /**
     * Fetch rates from repository (without cache)
     *
     * @return CurrencyRateDto|Collection<CurrencyRateDto>
     */
    protected function fetchRates(): CurrencyRateDto|Collection
    {
        if ($this->ccy) {
            return $this->repository->getRateByCcy($this->date, $this->ccy);
        }

        if ($this->numericCode) {
            return $this->repository->getRateByNumericCode($this->date, $this->numericCode);
        }

        return $this->repository->getRatesByDate($this->date);
    }

    /**
     * Generate cache key based on query parameters
     */
    protected function getCacheKey(): string
    {
        $parts = [
            'cbu_currency_rate',
            $this->date,
            $this->ccy?->value ?? 'all',
            $this->numericCode?->value ?? 'no_code',
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
        $cacheKey = 'cbu_currency_rates_all_'.$this->date.'_'.get_class($this->repository);

        return $this->remember($cacheKey, fn () => $this->repository->getRatesByDate($this->date));
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
