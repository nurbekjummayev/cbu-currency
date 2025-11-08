<?php

declare(strict_types=1);

namespace Cbu\Currency\Models;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'cbu_id',
        'code',
        'ccy',
        'name_ru',
        'name_uz',
        'name_oz',
        'name_en',
    ];

    protected $casts = [
        'ccy' => CurrencyCcy::class,
        'code' => CurrencyNumericCode::class,
    ];
}
