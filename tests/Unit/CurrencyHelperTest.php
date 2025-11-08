<?php

declare(strict_types=1);

use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Exceptions\CbuApiException;
use Illuminate\Support\Facades\Log;

describe('CurrencyHelper::isValidDate()', function () {
    test('validates correct date format', function () {
        expect(fn() => CurrencyHelper::isValidDate('2025-01-15'))->not->toThrow(CbuApiException::class);
        expect(fn() => CurrencyHelper::isValidDate('2024-12-31'))->not->toThrow(CbuApiException::class);
        expect(fn() => CurrencyHelper::isValidDate('2023-06-01'))->not->toThrow(CbuApiException::class);
    });

    test('throws exception for invalid date format', function () {
        CurrencyHelper::isValidDate('2025/01/15');
    })->throws(CbuApiException::class);

    test('throws exception for incorrect date format with wrong separator', function () {
        CurrencyHelper::isValidDate('2025.01.15');
    })->throws(CbuApiException::class);

    test('throws exception for invalid date values', function () {
        CurrencyHelper::isValidDate('2025-13-01'); // Invalid month
    })->throws(CbuApiException::class);

    test('throws exception for invalid day', function () {
        CurrencyHelper::isValidDate('2025-02-30'); // February doesn't have 30 days
    })->throws(CbuApiException::class);

    test('throws exception for malformed date string', function () {
        CurrencyHelper::isValidDate('not-a-date');
    })->throws(CbuApiException::class);

    test('throws exception for empty string', function () {
        CurrencyHelper::isValidDate('');
    })->throws(CbuApiException::class);

    test('accepts leap year dates', function () {
        expect(fn() => CurrencyHelper::isValidDate('2024-02-29'))->not->toThrow(CbuApiException::class);
    });

    test('throws exception for non-leap year February 29', function () {
        CurrencyHelper::isValidDate('2025-02-29');
    })->throws(CbuApiException::class);
});

describe('CurrencyHelper logging methods', function () {
    beforeEach(function () {
        Log::spy();
        config(['cbu-currency.log_enabled' => true]);
    });

    test('logInfo() calls Log::info with correct parameters', function () {
        CurrencyHelper::logInfo('Test message', ['key' => 'value']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Test message', ['key' => 'value', 'package' => 'cbu-currency']);
    });

    test('logDebug() calls Log::debug with correct parameters', function () {
        CurrencyHelper::logDebug('Debug message', ['debug' => true]);

        Log::shouldHaveReceived('debug')
            ->once()
            ->with('Debug message', ['debug' => true, 'package' => 'cbu-currency']);
    });

    test('logWarning() calls Log::warning with correct parameters', function () {
        CurrencyHelper::logWarning('Warning message');

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Warning message', ['package' => 'cbu-currency']);
    });

    test('logError() calls Log::error with correct parameters', function () {
        CurrencyHelper::logError('Error message', ['error' => 'details']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Error message', ['error' => 'details', 'package' => 'cbu-currency']);
    });

    test('does not log when logging is disabled', function () {
        config(['cbu-currency.log_enabled' => false]);

        CurrencyHelper::logInfo('Should not be logged');

        Log::shouldNotHaveReceived('info');
    });

    test('log() method handles different log levels', function () {
        CurrencyHelper::log('info', 'Info message');
        CurrencyHelper::log('debug', 'Debug message');
        CurrencyHelper::log('warning', 'Warning message');
        CurrencyHelper::log('error', 'Error message');

        Log::shouldHaveReceived('info')->once();
        Log::shouldHaveReceived('debug')->once();
        Log::shouldHaveReceived('warning')->once();
        Log::shouldHaveReceived('error')->once();
    });
});
