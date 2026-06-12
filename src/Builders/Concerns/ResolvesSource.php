<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders\Concerns;

use Cbu\Currency\Enums\CurrencySource;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;

/**
 * Allows a builder to switch its repository between API and Database sources.
 */
trait ResolvesSource
{
    /**
     * Set the data source (API or Database)
     */
    public function source(CurrencySource $source): static
    {
        $this->repository = match ($source) {
            CurrencySource::API => app(ApiCurrencyRepository::class),
            CurrencySource::DATABASE => app(DatabaseCurrencyRepository::class),
        };

        return $this;
    }
}
