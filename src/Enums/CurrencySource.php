<?php

declare(strict_types=1);

namespace Cbu\Currency\Enums;

enum CurrencySource: string
{
    case DATABASE = 'database';
    case API = 'api';
}
