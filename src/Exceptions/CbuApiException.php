<?php

declare(strict_types=1);

namespace Cbu\Currency\Exceptions;

use Exception;

class CbuApiException extends Exception
{
    public static function requestFailed(string $url, int $status): self
    {
        return new self("CBU API request failed. URL: {$url}, Status: {$status}");
    }

    public static function noDataReceived(string $url): self
    {
        return new self("No data received from CBU API. URL: {$url}");
    }

    public static function connectionError(string $url, string $message): self
    {
        return new self("Connection error to CBU API. URL: {$url}, Error: {$message}");
    }

    public static function dateFormatInvalid(string $date, string $message): self
    {
        return new self("Invalid date format. Please use Y-m-d format (e.g., $date) or $message");
    }

    public static function rateNotFound(string $currencyCode, string $date): self
    {
        return new self("Rate not found for currency {$currencyCode} on {$date}");
    }

    public static function missingRequiredParameters(array $missing): self
    {
        $fields = implode(', ', $missing);

        return new self("Missing required parameters: {$fields}");
    }

    public static function invalidQueryParameters(string $message = 'Invalid query parameters'): self
    {
        return new self($message);
    }

    public static function invalidAmount(float $amount): self
    {
        return new self("Invalid amount: {$amount}. Amount must be greater than 0");
    }
}
