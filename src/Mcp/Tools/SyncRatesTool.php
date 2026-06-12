<?php

declare(strict_types=1);

namespace Cbu\Currency\Mcp\Tools;

use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Facades\CbuCurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sync exchange rates from the CBU API into the local database. Provide a specific date, a number of last days, or neither to sync today. Returns statistics about saved and updated rates.')]
class SyncRatesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'last_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $sync = CbuCurrency::sync();

        if (isset($validated['date'])) {
            $sync->date($validated['date']);
        } elseif (isset($validated['last_days'])) {
            $sync->lastDays((int) $validated['last_days']);
        }

        try {
            $result = $sync->save();
        } catch (CbuApiException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('Specific date to sync in Y-m-d format. Cannot be in the future.'),
            'last_days' => $schema->integer()
                ->description('Sync rates for the last N days (1-365). Ignored when "date" is provided. When neither is given, today is synced.'),
        ];
    }
}
