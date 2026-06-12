<?php

declare(strict_types=1);

namespace Cbu\Currency\Mcp;

use Cbu\Currency\Mcp\Tools\ConvertCurrencyTool;
use Cbu\Currency\Mcp\Tools\GetRatesTool;
use Cbu\Currency\Mcp\Tools\GetRateTool;
use Cbu\Currency\Mcp\Tools\ListCurrenciesTool;
use Cbu\Currency\Mcp\Tools\SyncRatesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('CBU Currency')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
This server provides Central Bank of Uzbekistan (CBU) exchange rates.
All rates are quoted against UZS (Uzbekistani so'm). Conversions between
two foreign currencies go through UZS as the intermediate. Dates use the
Y-m-d format. Results are returned at full precision unless a scale is
provided, in which case the final result is rounded half-up.
TEXT)]
class CbuCurrencyServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetRatesTool::class,
        GetRateTool::class,
        ConvertCurrencyTool::class,
        ListCurrenciesTool::class,
        SyncRatesTool::class,
    ];
}
