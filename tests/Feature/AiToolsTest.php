<?php

declare(strict_types=1);

use Cbu\Currency\Ai\CbuCurrencyAgent;
use Cbu\Currency\Ai\Tools\ConvertCurrencyTool;
use Cbu\Currency\Ai\Tools\GetConversionRateTool;
use Cbu\Currency\Ai\Tools\GetCurrencyTool;
use Cbu\Currency\Ai\Tools\GetRatesTool;
use Cbu\Currency\Ai\Tools\GetRateTool;
use Cbu\Currency\Ai\Tools\ListCurrenciesTool;
use Cbu\Currency\Ai\Tools\ListCurrencyCodesTool;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

// The Laravel AI SDK (laravel/ai) requires PHP 8.3+, so it is an optional dev
// dependency installed only on the Laravel 13+ CI jobs. Skip these tests when
// it is not present instead of failing to autoload the tool classes.
beforeEach(function () {
    if (! interface_exists(Tool::class)) {
        test()->markTestSkipped('laravel/ai is not installed (requires PHP 8.3+).');
    }

    // Drive the tools against the live API repository so the HTTP fakes below
    // are exercised directly (the shared TestCase defaults source to "database").
    config()->set('cbu-currency.source', 'api');
});

function aiUsdPayload(float $rate = 12352.86, string $date = '03.01.2024'): array
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

/** Decode a tool's JSON string response into an array. */
function callTool(Tool $tool, array $args = []): array
{
    return json_decode($tool->handle(new Request($args)), true, flags: JSON_THROW_ON_ERROR);
}

it('converts an amount and returns the full from/to rate breakdown', function () {
    Http::fake([
        '*/USD/2024-01-03/' => Http::response([aiUsdPayload()]),
    ]);

    $out = callTool(new ConvertCurrencyTool, [
        'amount' => 100,
        'from' => 'USD',
        'to' => 'UZS',
        'date' => '2024-01-03',
    ]);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['from_currency'])->toBe('USD')
        ->and($out['data']['to_currency'])->toBe('UZS')
        ->and($out['data']['from_rate'])->toEqual(12352.86)
        ->and($out['data']['to_rate'])->toEqual(1)
        ->and($out['data']['result'])->toEqual(1235286)
        ->and($out['data']['amount_in_uzs'])->toEqual(1235286)
        ->and($out['data']['date'])->toBe('2024-01-03')
        ->and($out['data'])->toHaveKey('explanation')
        ->and($out['summary'])->toContain('100 USD')
        ->and($out['summary'])->toContain('UZS');
});

it('returns the rate between two currencies for amount = 1', function () {
    Http::fake([
        '*/USD/2024-01-03/' => Http::response([aiUsdPayload()]),
    ]);

    $out = callTool(new GetConversionRateTool, [
        'from' => 'USD',
        'to' => 'UZS',
        'date' => '2024-01-03',
    ]);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['amount'])->toEqual(1)
        ->and($out['data']['result'])->toEqual(12352.86)
        ->and($out['summary'])->toContain('1 USD');
});

it('gets a single currency rate for a date', function () {
    Http::fake([
        '*/USD/2024-01-03/' => Http::response([aiUsdPayload()]),
    ]);

    $out = callTool(new GetRateTool, ['ccy' => 'USD', 'date' => '2024-01-03']);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['rate'])->toBe(12352.86)
        ->and($out['data']['ccy'])->toBe('USD')
        ->and($out['data']['nominal'])->toBe(1)
        ->and($out['summary'])->toContain('12352.86');
});

it('gets all rates for a date', function () {
    Http::fake([
        '*/all/2024-01-03/' => Http::response([aiUsdPayload()]),
    ]);

    $out = callTool(new GetRatesTool, ['date' => '2024-01-03']);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['count'])->toBe(1)
        ->and($out['data']['rates'][0]['ccy'])->toBe('USD');
});

it('lists all currency codes without any HTTP call', function () {
    Http::fake();

    $out = callTool(new ListCurrencyCodesTool);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['codes'])->toContain('USD', 'EUR', 'UZS')
        ->and($out['data']['count'])->toBe(count($out['data']['codes']));

    Http::assertNothingSent();
});

it('lists all currencies with names', function () {
    Http::fake([
        '*/all/*' => Http::response([aiUsdPayload(date: now()->format('d.m.Y'))]),
    ]);

    $out = callTool(new ListCurrenciesTool);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['currencies'][0]['name_en'])->toBe('US Dollar');
});

it('gets a single currency by code', function () {
    Http::fake([
        '*/USD/*' => Http::response([aiUsdPayload(date: now()->format('d.m.Y'))]),
    ]);

    $out = callTool(new GetCurrencyTool, ['ccy' => 'USD']);

    expect($out['success'])->toBeTrue()
        ->and($out['data']['ccy'])->toBe('USD')
        ->and($out['data']['name_en'])->toBe('US Dollar');
});

it('returns a structured error for invalid arguments', function () {
    Http::fake();

    $out = callTool(new ConvertCurrencyTool, [
        'amount' => -5,
        'from' => 'XYZ',
        'to' => 'UZS',
    ]);

    expect($out['success'])->toBeFalse()
        ->and($out['error'])->toContain('invalid')
        ->and($out['validation'])->toHaveKeys(['amount', 'from']);

    Http::assertNothingSent();
});

it('registers all seven tools on the agent', function () {
    $tools = iterator_to_array((function () {
        yield from (new CbuCurrencyAgent)->tools();
    })());

    expect($tools)->toHaveCount(7)
        ->and($tools)->each->toBeInstanceOf(Tool::class);
});
