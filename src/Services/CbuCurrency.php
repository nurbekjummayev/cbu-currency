<?php

declare(strict_types=1);

namespace Cbu\Currency\Services;

use Cbu\Currency\Builders\ConvertBuilder;
use Cbu\Currency\Builders\CurrencyBuilder;
use Cbu\Currency\Builders\RatesBuilder;
use Cbu\Currency\Builders\SyncBuilder;
use Cbu\Currency\Enums\CurrencySource;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;

/**
 * CbuCurrency Service
 *
 * O'zbekiston Markaziy Banki (CBU) valyuta kurslari bilan ishlash uchun asosiy service.
 */
class CbuCurrency
{
    protected int $scale;

    protected CurrencySource $source;

    protected CurrencyRepositoryInterface $repository;

    public function __construct()
    {
        $this->scale = config('cbu-currency.scale', 2);
        $this->source = CurrencySource::from(config('cbu-currency.source', 'api'));
        $this->repository = $this->resolveRepository();
    }

    /**
     * Resolve the repository based on the configured source
     */
    protected function resolveRepository(): CurrencyRepositoryInterface
    {
        return $this->source === CurrencySource::API
            ? app(ApiCurrencyRepository::class)
            : app(DatabaseCurrencyRepository::class);
    }

    /**
     * Start building a currency conversion
     */
    public function convert(): ConvertBuilder
    {
        return new ConvertBuilder($this->repository);
    }

    /**
     * Start building a rates query
     */
    public function rates(): RatesBuilder
    {
        return new RatesBuilder($this->repository);
    }

    /**
     * Start building a sync operation (API to Database)
     */
    public function sync(): SyncBuilder
    {
        return new SyncBuilder(
            app(ApiCurrencyRepository::class),
            app(DatabaseCurrencyRepository::class)
        );
    }

    /**
     * Start building a currency query
     *
     * Fetches currency information from configured source (API or Database).
     * Use source() method on the builder to change the source.
     *
     * @example
     * // Get from database (default source from config)
     * CbuCurrency::currencies()->all();
     *
     * // Get from API
     * CbuCurrency::currencies()->source(CurrencySource::API)->all();
     */
    public function currencies(): CurrencyBuilder
    {
        return new CurrencyBuilder($this->repository);
    }
}
