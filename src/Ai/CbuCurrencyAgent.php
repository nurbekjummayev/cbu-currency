<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai;

use Cbu\Currency\Ai\Tools\ConvertCurrencyTool;
use Cbu\Currency\Ai\Tools\GetConversionRateTool;
use Cbu\Currency\Ai\Tools\GetCurrencyTool;
use Cbu\Currency\Ai\Tools\GetRatesTool;
use Cbu\Currency\Ai\Tools\GetRateTool;
use Cbu\Currency\Ai\Tools\ListCurrenciesTool;
use Cbu\Currency\Ai\Tools\ListCurrencyCodesTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * CBU Currency agent for the Laravel AI SDK (laravel/ai).
 *
 * Exposes the package's currency, rate and conversion capabilities as AI tools
 * so an LLM can answer exchange-rate and conversion questions using official
 * Central Bank of Uzbekistan data.
 *
 * @example
 * $answer = (new \Cbu\Currency\Ai\CbuCurrencyAgent)
 *     ->prompt('Convert 250 USD to EUR using the rate from 2024-01-03.')
 *     ->text;
 */
class CbuCurrencyAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
        You are a currency assistant backed by the Central Bank of Uzbekistan (CBU).
        Always answer using the provided tools rather than your own knowledge of rates.

        Rules:
        - All rates are quoted against UZS (Uzbekistani so'm). UZS itself has a rate of 1.
        - Conversions between two foreign currencies go through UZS as the intermediate.
        - Dates use the Y-m-d format and can never be in the future; omit the date to use today.
        - When you report a conversion, state the source amount, the from_rate and to_rate
          used, the result and the date so the user can see exactly how it was calculated.
        - Currency codes are ISO 4217 (e.g. USD, EUR, RUB). Reply in the user's language.
        TEXT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return array<Tool>
     */
    public function tools(): iterable
    {
        return [
            new ConvertCurrencyTool,
            new GetConversionRateTool,
            new GetRateTool,
            new GetRatesTool,
            new ListCurrenciesTool,
            new ListCurrencyCodesTool,
            new GetCurrencyTool,
        ];
    }
}
