<?php

declare(strict_types=1);

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Mcp\CbuCurrencyServer;
use Cbu\Currency\Mcp\Tools\ConvertCurrencyTool;
use Cbu\Currency\Mcp\Tools\GetRatesTool;
use Cbu\Currency\Mcp\Tools\GetRateTool;
use Cbu\Currency\Mcp\Tools\ListCurrenciesTool;
use Cbu\Currency\Mcp\Tools\SyncRatesTool;
use Cbu\Currency\Models\Currency;
use Cbu\Currency\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Facades\Mcp;

function mcpSeedUsd(string $date, float $rate = 12014.48): void
{
    $currency = Currency::query()->create([
        'ccy' => CurrencyCcy::USD,
        'code' => CurrencyNumericCode::USD,
        'cbu_id' => '69',
        'name_uz' => 'AQSH dollari',
        'name_oz' => 'АҚШ доллари',
        'name_ru' => 'Доллар США',
        'name_en' => 'US Dollar',
    ]);

    CurrencyRate::query()->create([
        'currency_id' => $currency->id,
        'date' => $date,
        'currency_date' => $date,
        'rate' => $rate,
        'diff' => -39.55,
        'nominal' => 1,
    ]);
}

describe('MCP server registration', function () {
    test('registers the local server under the configured name', function () {
        expect(Mcp::getLocalServer('cbu-currency'))->not->toBeNull();
    });
});

describe('MCP tools', function () {
    test('get-rates tool returns all rates for a date', function () {
        $date = now()->format('Y-m-d');
        mcpSeedUsd($date);

        $response = CbuCurrencyServer::tool(GetRatesTool::class, ['date' => $date]);

        $response->assertOk()
            ->assertSee('USD')
            ->assertSee('12014.48');
    });

    test('get-rate tool returns a single currency rate', function () {
        $date = now()->format('Y-m-d');
        mcpSeedUsd($date);

        $response = CbuCurrencyServer::tool(GetRateTool::class, [
            'ccy' => 'USD',
            'date' => $date,
        ]);

        $response->assertOk()->assertSee('12014.48');
    });

    test('get-rate tool rejects an unknown currency code', function () {
        $response = CbuCurrencyServer::tool(GetRateTool::class, ['ccy' => 'XXX']);

        $response->assertHasErrors();
    });

    test('convert tool returns the full-precision result without scale', function () {
        $date = now()->format('Y-m-d');
        mcpSeedUsd($date);

        $response = CbuCurrencyServer::tool(ConvertCurrencyTool::class, [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'UZS',
            'date' => $date,
        ]);

        $response->assertOk()->assertSee('1201448');
    });

    test('convert tool rounds the final result when scale is provided', function () {
        $date = now()->format('Y-m-d');
        mcpSeedUsd($date);

        $response = CbuCurrencyServer::tool(ConvertCurrencyTool::class, [
            'amount' => 100,
            'from' => 'UZS',
            'to' => 'USD',
            'date' => $date,
            'scale' => 4,
        ]);

        $response->assertOk()->assertSee((string) round(100 / 12014.48, 4));
    });

    test('convert tool rejects a non-positive amount', function () {
        $response = CbuCurrencyServer::tool(ConvertCurrencyTool::class, [
            'amount' => 0,
            'from' => 'USD',
            'to' => 'UZS',
        ]);

        $response->assertHasErrors();
    });

    test('list-currencies tool returns currency metadata', function () {
        mcpSeedUsd(now()->format('Y-m-d'));

        $response = CbuCurrencyServer::tool(ListCurrenciesTool::class, []);

        $response->assertOk()
            ->assertSee('US Dollar')
            ->assertSee('AQSH dollari');
    });

    test('list-currencies tool returns a single currency by code', function () {
        mcpSeedUsd(now()->format('Y-m-d'));

        $response = CbuCurrencyServer::tool(ListCurrenciesTool::class, ['ccy' => 'USD']);

        $response->assertOk()->assertSee('US Dollar');
    });

    test('sync-rates tool fetches from the API and stores rates', function () {
        $date = now()->format('Y-m-d');

        Http::fake([
            '*' => Http::response([
                [
                    'id' => 69,
                    'Code' => '840',
                    'Ccy' => 'USD',
                    'CcyNm_RU' => 'Доллар США',
                    'CcyNm_UZ' => 'AQSH dollari',
                    'CcyNm_UZC' => 'АҚШ доллари',
                    'CcyNm_EN' => 'US Dollar',
                    'Nominal' => '1',
                    'Rate' => '12014.48',
                    'Diff' => '-39.55',
                    'Date' => '12.06.2026',
                ],
            ]),
        ]);

        $response = CbuCurrencyServer::tool(SyncRatesTool::class, ['date' => $date]);

        $response->assertOk()->assertSee('rates_saved');

        expect(CurrencyRate::query()->count())->toBe(1);
    });
});
