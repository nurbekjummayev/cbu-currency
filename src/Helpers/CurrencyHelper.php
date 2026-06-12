<?php

declare(strict_types=1);

namespace Cbu\Currency\Helpers;

use Cbu\Currency\Exceptions\CbuApiException;
use DateTime;
use Illuminate\Support\Facades\Log;

/**
 * Currency Helper - Utility functions for currency operations
 *
 * Provides common helper methods including date validation and centralized logging.
 */
class CurrencyHelper
{
    /**
     * Validate date format (Y-m-d)
     *
     * @param  string  $date  Date to validate
     *
     * @throws CbuApiException
     */
    public static function isValidDate(string $date): void
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            throw CbuApiException::dateFormatInvalid($date, '');
        }
    }

    /**
     * Round a decimal string to the given scale using half-up rounding
     *
     * BCMath operations truncate instead of rounding, so this adds half of
     * the smallest representable unit before truncating (works for negative
     * numbers as well).
     *
     * @param  string  $value  Decimal number as a string (e.g. '1.38674333')
     * @param  int  $scale  Number of decimal places to keep
     */
    public static function bcRound(string $value, int $scale = 0): string
    {
        if (! str_contains($value, '.')) {
            return bcadd($value, '0', $scale);
        }

        $delta = '0.'.str_repeat('0', $scale).'5';

        return str_starts_with($value, '-')
            ? bcsub($value, $delta, $scale)
            : bcadd($value, $delta, $scale);
    }

    /**
     * Centralized logging method with configuration support
     *
     * Logs messages only if logging is enabled in configuration.
     * Uses Laravel's Log facade with contextual information.
     *
     * @param  string  $level  Log level (info, debug, warning, error)
     * @param  string  $message  Log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        // Check if logging is enabled in config
        if (! config('cbu-currency.log_enabled', true)) {
            return;
        }

        // Add package identifier to context
        $context['package'] = 'cbu-currency';

        // Log based on level
        match ($level) {
            'info' => Log::info($message, $context),
            'debug' => Log::debug($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
        };
    }

    /**
     * Log info level message
     *
     * @param  string  $message  Log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function logInfo(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Log debug level message
     *
     * @param  string  $message  Log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function logDebug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    /**
     * Log warning level message
     *
     * @param  string  $message  Log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function logWarning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Log error level message
     *
     * @param  string  $message  Log message
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function logError(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}
