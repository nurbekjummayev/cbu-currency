<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\Builders\Concerns\ResolvesSource;
use Cbu\Currency\DTOs\CurrencyDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Currency Builder - Fluent builder for fetching currency information
 *
 * Provides a convenient interface for retrieving currency data
 * from either the API or database.
 *
 * @example
 * // Get all currencies from database (default)
 * CbuCurrency::currencies()->all();
 *
 * // Get all currencies from API
 * CbuCurrency::currencies()->source(CurrencySource::API)->all();
 *
 * // Get specific currency by code
 * CbuCurrency::currencies()->ccy('USD')->get();
 *
 * // Get specific currency from API
 * CbuCurrency::currencies()->source(CurrencySource::API)->ccy('EUR')->get();
 */
class CurrencyBuilder
{
    use ResolvesSource;

    protected ?CurrencyCcy $ccy = null;

    public function __construct(
        protected CurrencyRepositoryInterface $repository
    ) {}

    /**
     * Set the currency code to fetch
     *
     * @param  CurrencyCcy|string  $ccy  Currency code
     */
    public function ccy(CurrencyCcy|string $ccy): self
    {
        $this->ccy = $ccy instanceof CurrencyCcy ? $ccy : CurrencyCcy::from(strtoupper($ccy));

        return $this;
    }

    /**
     * Get all currencies
     *
     * Returns all currencies ordered by currency code.
     *
     * @return Collection<CurrencyDto>
     */
    public function all(): Collection
    {
        return $this->repository->getAllCurrencies();
    }

    /**
     * Get specific currency or all if no code specified
     *
     * @return CurrencyDto|Collection<CurrencyDto>|null
     */
    public function get(): CurrencyDto|Collection|null
    {
        if ($this->ccy) {
            return $this->repository->getCurrencyByCode($this->ccy->value);
        }

        return $this->all();
    }
}
