<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\DTOs\ConversionResultDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Enums\CurrencySource;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\ApiCurrencyRepository;
use Cbu\Currency\Repositories\DatabaseCurrencyRepository;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ConvertBuilder
{
    protected ?CurrencyCcy $fromCurrency = null;

    protected ?CurrencyCcy $toCurrency = null;

    protected ?float $amount = null;

    protected ?string $date = null;

    protected int $scale;

    protected ?int $cacheDuration = null;

    public function __construct(
        protected CurrencyRepositoryInterface $repository
    ) {
        $this->scale = config('cbu-currency.scale', 2);
        $this->cacheDuration = config('cbu-currency.cache_duration');
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Set the data source (API or Database)
     */
    public function source(CurrencySource $source): self
    {
        $this->repository = match ($source) {
            CurrencySource::API => app(ApiCurrencyRepository::class),
            CurrencySource::DATABASE => app(DatabaseCurrencyRepository::class),
        };

        return $this;
    }

    /**
     * Set the source currency
     */
    public function from(CurrencyCcy|string $currency): self
    {
        $this->fromCurrency = $currency instanceof CurrencyCcy
            ? $currency
            : CurrencyCcy::from(strtoupper($currency));

        return $this;
    }

    /**
     * Set the source currency by numeric code
     */
    public function fromCode(CurrencyNumericCode|int $code): self
    {
        $numericCode = $code instanceof CurrencyNumericCode ? $code : CurrencyNumericCode::from($code);
        $this->fromCurrency = CurrencyCcy::from($numericCode->name);

        return $this;
    }

    /**
     * Set the target currency
     */
    public function to(CurrencyCcy|string $currency): self
    {
        $this->toCurrency = $currency instanceof CurrencyCcy
            ? $currency
            : CurrencyCcy::from(strtoupper($currency));

        return $this;
    }

    /**
     * Set the target currency by numeric code
     */
    public function toCode(CurrencyNumericCode|int $code): self
    {
        $numericCode = $code instanceof CurrencyNumericCode ? $code : CurrencyNumericCode::from($code);
        $this->toCurrency = CurrencyCcy::from($numericCode->name);

        return $this;
    }

    /**
     * Set the amount to convert
     */
    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the date for conversion
     *
     * @throws CbuApiException
     */
    public function date(string $date): self
    {
        CurrencyHelper::isValidDate($date);
        $this->date = $date;

        return $this;
    }

    /**
     * Set the date for conversion
     */
    public function today(): self
    {
        $this->date = now()->format('Y-m-d');

        return $this;
    }

    /**
     * Enable caching for currency rates (not conversion result)
     *
     * @param  int|null  $minutes  Cache duration in minutes (null = use config default)
     */
    public function cache(?int $minutes = null): self
    {
        $this->cacheDuration = $minutes;

        return $this;
    }

    /**
     * Execute the conversion
     *
     * @throws CbuApiException
     */
    public function get(): ConversionResultDto
    {
        $missing = [];
        if (! $this->fromCurrency) {
            $missing[] = 'from';
        }
        if (! $this->toCurrency) {
            $missing[] = 'to';
        }
        if ($this->amount === null) {
            $missing[] = 'amount';
        }

        if (! empty($missing)) {
            throw CbuApiException::missingRequiredParameters($missing);
        }

        if ($this->amount <= 0) {
            throw CbuApiException::invalidAmount($this->amount);
        }

        $date = $this->date;

        if ($this->fromCurrency === $this->toCurrency) {
            $result = $this->sameCurrencyConversion($date);
        } elseif ($this->fromCurrency === CurrencyCcy::UZS) {
            $result = $this->fromUzs($date);
        } elseif ($this->toCurrency === CurrencyCcy::UZS) {
            $result = $this->toUzs($date);
        } else {
            $result = $this->crossConversion($date);
        }

        return $result;
    }

    /**
     * Get rate with optional caching
     */
    protected function getRate(string $date, string $ccy): float
    {
        if ($this->cacheDuration !== null and $this->cacheDuration > 0) {
            $cacheKey = "cbu_currency_rate_{$date}_{$ccy}_".get_class($this->repository);

            return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($date, $ccy) {
                return $this->repository->getRateByCcy($date, $ccy)->rate;
            });
        }

        return $this->repository->getRateByCcy($date, $ccy)->rate;
    }

    /**
     * Handle same currency conversion
     */
    protected function sameCurrencyConversion(string $date): ConversionResultDto
    {
        $rate = null;
        $amountInUzs = $this->amount;

        if ($this->fromCurrency !== CurrencyCcy::UZS) {
            $rate = $this->getRate($date, $this->fromCurrency->value);
            $amountInUzs = (float) bcmul((string) $this->amount, (string) $rate, $this->scale);
        }

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: $this->amount,
            fromRate: $rate,
            toRate: $rate,
            amountInUzs: $amountInUzs,
            date: $date,
        );
    }

    /**
     * Convert from UZS to foreign currency
     */
    protected function fromUzs(string $date): ConversionResultDto
    {
        $rate = $this->getRate($date, $this->toCurrency->value);
        $result = bcdiv((string) $this->amount, (string) $rate, $this->scale);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: (float) $result,
            fromRate: 1,
            toRate: $rate,
            amountInUzs: $this->amount,
            date: $date,
        );
    }

    /**
     * Convert from foreign currency to UZS
     */
    protected function toUzs(string $date): ConversionResultDto
    {
        $rate = $this->getRate($date, $this->fromCurrency->value);
        $result = bcmul((string) $this->amount, (string) $rate, $this->scale);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: (float) $result,
            fromRate: $rate,
            toRate: 1,
            amountInUzs: (float) $result,
            date: $date,
        );
    }

    /**
     * Convert between two foreign currencies
     */
    protected function crossConversion(string $date): ConversionResultDto
    {
        $fromRate = $this->getRate($date, $this->fromCurrency->value);
        $toRate = $this->getRate($date, $this->toCurrency->value);

        $amountInUzs = bcmul((string) $this->amount, (string) $fromRate, $this->scale);
        $result = bcdiv($amountInUzs, (string) $toRate, $this->scale);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: (float) $result,
            fromRate: $fromRate,
            toRate: $toRate,
            amountInUzs: (float) $amountInUzs,
            date: $date,
        );
    }
}
