<?php

declare(strict_types=1);

namespace Cbu\Currency\DTOs;

class ConversionResultDto
{
    public function __construct(
        public readonly float $amount,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $result,
        public readonly ?float $fromRate,
        public readonly ?float $toRate,
        public readonly float $amountInUzs,
        public readonly string $date,
    ) {}

    /**
     * Return a copy with the result and UZS amount rounded (half-up)
     *
     * Rates are left untouched — only the computed amounts are rounded.
     *
     * @param  int  $decimals  Number of decimal places
     */
    public function round(int $decimals): self
    {
        $decimals = max(0, $decimals);

        return new self(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency,
            toCurrency: $this->toCurrency,
            result: round($this->result, $decimals),
            fromRate: $this->fromRate,
            toRate: $this->toRate,
            amountInUzs: round($this->amountInUzs, $decimals),
            date: $this->date,
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'from_currency' => $this->fromCurrency,
            'to_currency' => $this->toCurrency,
            'result' => $this->result,
            'from_rate' => $this->fromRate,
            'to_rate' => $this->toRate,
            'amount_in_uzs' => $this->amountInUzs,
            'date' => $this->date,
        ];
    }
}
