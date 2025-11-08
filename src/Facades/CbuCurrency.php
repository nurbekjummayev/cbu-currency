<?php

declare(strict_types=1);

namespace Cbu\Currency\Facades;

use Cbu\Currency\Builders\ConvertBuilder;
use Cbu\Currency\Builders\CurrencyBuilder;
use Cbu\Currency\Builders\RatesBuilder;
use Cbu\Currency\Builders\SyncBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ConvertBuilder convert()
 * @method static RatesBuilder rates()
 * @method static SyncBuilder sync()
 * @method static CurrencyBuilder currencies()
 */
class CbuCurrency extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cbu-currency';
    }
}
