<?php

declare(strict_types=1);

namespace Cbu\Currency\Ai\Tools;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Exceptions\CbuApiException;
use Closure;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

/**
 * Base class for the CBU Currency AI tools (laravel/ai).
 *
 * Centralizes argument validation, error handling and the JSON response
 * shape so every tool returns a consistent, English, self-describing payload
 * back to the agent.
 */
abstract class AbstractCurrencyTool implements Tool
{
    /**
     * Validate the incoming arguments, run the callback and normalize errors.
     *
     * The callback receives the validated arguments and must return the array
     * of data to send back to the model. Any CBU/API failure is turned into a
     * structured error response instead of bubbling up as an exception.
     *
     * @param  array<string, mixed>  $rules
     * @param  Closure(array<string, mixed>): array<string, mixed>  $callback
     */
    protected function run(Request $request, array $rules, Closure $callback): string
    {
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error(
                'The provided arguments are invalid.',
                ['validation' => $validator->errors()->toArray()],
            );
        }

        try {
            [$summary, $data] = $callback($validator->validated());

            return $this->success($summary, $data);
        } catch (CbuApiException $e) {
            return $this->error($e->getMessage());
        } catch (Throwable $e) {
            return $this->error('An unexpected error occurred: '.$e->getMessage());
        }
    }

    /**
     * Build a successful JSON response.
     *
     * @param  array<string, mixed>|list<mixed>  $data
     */
    protected function success(string $summary, array $data): string
    {
        return $this->encode([
            'success' => true,
            'summary' => $summary,
            'data' => $data,
        ]);
    }

    /**
     * Build an error JSON response.
     *
     * @param  array<string, mixed>  $context
     */
    protected function error(string $message, array $context = []): string
    {
        return $this->encode([
            'success' => false,
            'error' => $message,
        ] + $context);
    }

    /**
     * All ISO 4217 currency codes supported by CBU (e.g. USD, EUR, UZS).
     *
     * @return list<string>
     */
    protected function currencyCodes(): array
    {
        return array_column(CurrencyCcy::cases(), 'value');
    }

    /**
     * Encode a payload as pretty, human-readable UTF-8 JSON.
     *
     * @param  array<string, mixed>  $payload
     */
    private function encode(array $payload): string
    {
        return (string) json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }
}
