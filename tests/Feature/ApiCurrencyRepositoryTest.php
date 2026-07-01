<?php

declare(strict_types=1);

use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Illuminate\Support\Facades\Http;

function apiUsdPayload(float $rate = 12352.86, string $date = '03.01.2024'): array
{
    return [
        'id' => 69,
        'Code' => '840',
        'Ccy' => 'USD',
        'CcyNm_RU' => 'Доллар США',
        'CcyNm_UZ' => 'AQSH dollari',
        'CcyNm_UZC' => 'АҚШ доллари',
        'CcyNm_EN' => 'US Dollar',
        'Nominal' => '1',
        'Rate' => (string) $rate,
        'Diff' => '-39.55',
        'Date' => $date,
    ];
}

it('fetches the archived rate for a past date, not today', function () {
    Http::fake([
        '*/USD/2024-01-03/' => Http::response([apiUsdPayload()]),
    ]);

    $rate = app(ApiCurrencyRepository::class)->getRateByCcy('2024-01-03', 'USD');

    expect($rate->rate)->toBe(12352.86);
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/USD/2024-01-03/'));
});

it('requests the archived list for a past date with a trailing slash', function () {
    Http::fake([
        '*/all/2024-01-03/' => Http::response([apiUsdPayload()]),
    ]);

    app(ApiCurrencyRepository::class)->getRatesByDate('2024-01-03');

    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/all/2024-01-03/'));
});
