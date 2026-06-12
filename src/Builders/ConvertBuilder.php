<?php

declare(strict_types=1);

namespace Cbu\Currency\Builders;

use Cbu\Currency\Builders\Concerns\HandlesCaching;
use Cbu\Currency\Builders\Concerns\ResolvesSource;
use Cbu\Currency\DTOs\ConversionResultDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;

class ConvertBuilder
{
    use HandlesCaching;
    use ResolvesSource;

    /**
     * Scale used for all intermediate BCMath operations.
     *
     * Intermediate values are never rounded. The final result is returned
     * at full precision unless a positive scale is configured, in which
     * case only the final result is rounded (half-up).
     */
    protected const INTERNAL_SCALE = 20;

    protected ?CurrencyCcy $fromCurrency = null;

    protected ?CurrencyCcy $toCurrency = null;

    protected ?float $amount = null;

    protected string $date;

    protected int $scale;

    public function __construct(
        protected CurrencyRepositoryInterface $repository
    ) {
        $this->scale = (int) config('cbu-currency.scale', 0);
        $this->cacheDuration = config('cbu-currency.cache_duration');
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Set the number of decimal places for the final result
     *
     * By default (scale 0) the result is NOT rounded — it is returned at
     * full computed precision. Set a positive scale to round the final
     * result half-up to that many decimal places.
     */
    public function scale(int $decimals): self
    {
        $this->scale = max(0, $decimals);

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

        // is_finite() also rejects NAN, which slips past comparison checks
        // because every comparison with NAN evaluates to false.
        if (! is_finite($this->amount) || $this->amount <= 0) {
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
     * Get the UZS rate for 1 unit of the given currency, with optional caching
     *
     * CBU quotes some currencies per nominal (e.g. IDR, IRR, VND are quoted
     * per 10 units), so the raw rate is divided by the nominal to get the
     * per-unit rate. Returned as a high-precision decimal string.
     */
    protected function getUnitRate(string $date, string $ccy): string
    {
        $cacheKey = "cbu_currency_unit_rate_{$date}_{$ccy}_".get_class($this->repository);

        return $this->remember($cacheKey, function () use ($date, $ccy) {
            $dto = $this->repository->getRateByCcy($date, $ccy);

            // Guard against bad upstream data: a zero rate would cause a
            // DivisionByZeroError, a negative rate a nonsense conversion.
            if (! is_finite($dto->rate) || $dto->rate <= 0) {
                throw CbuApiException::invalidRate($ccy, $dto->rate, $date);
            }

            $nominal = max(1, $dto->nominal);

            return bcdiv(self::toDecimal($dto->rate), (string) $nominal, self::INTERNAL_SCALE);
        });
    }

    /**
     * Convert a float to a plain decimal string for BCMath
     *
     * Uses PHP's shortest round-trip representation (so 4.56 stays "4.56"
     * instead of exposing binary noise like "4.5600000000000000053"), and
     * expands scientific notation (e.g. 1.0E-7) into a plain decimal string.
     */
    protected static function toDecimal(float $value): string
    {
        $string = (string) $value;

        if (stripos($string, 'e') !== false) {
            $string = rtrim(sprintf('%.'.self::INTERNAL_SCALE.'F', $value), '0');
            $string = rtrim($string, '.') ?: '0';
        }

        return $string;
    }

    /**
     * Finalize a high-precision decimal string into a float
     *
     * When a positive scale is set, the value is rounded half-up to that
     * many decimals; otherwise it is returned at full precision.
     */
    protected function finalize(string $value): float
    {
        if ($this->scale > 0) {
            return (float) CurrencyHelper::bcRound($value, $this->scale);
        }

        return (float) $value;
    }

    /**
     * Handle same currency conversion
     */
    protected function sameCurrencyConversion(string $date): ConversionResultDto
    {
        $rate = null;
        $amountInUzs = self::toDecimal($this->amount);

        if ($this->fromCurrency !== CurrencyCcy::UZS) {
            $rate = $this->getUnitRate($date, $this->fromCurrency->value);
            $amountInUzs = bcmul(self::toDecimal($this->amount), $rate, self::INTERNAL_SCALE);
        }

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: $this->amount,
            fromRate: $rate !== null ? (float) $rate : null,
            toRate: $rate !== null ? (float) $rate : null,
            amountInUzs: $this->finalize($amountInUzs),
            date: $date,
        );
    }

    /**
     * Convert from UZS to foreign currency
     */
    protected function fromUzs(string $date): ConversionResultDto
    {
        $rate = $this->getUnitRate($date, $this->toCurrency->value);
        $result = bcdiv(self::toDecimal($this->amount), $rate, self::INTERNAL_SCALE);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: $this->finalize($result),
            fromRate: 1,
            toRate: (float) $rate,
            amountInUzs: $this->amount,
            date: $date,
        );
    }

    /**
     * Convert from foreign currency to UZS
     */
    protected function toUzs(string $date): ConversionResultDto
    {
        $rate = $this->getUnitRate($date, $this->fromCurrency->value);
        $result = bcmul(self::toDecimal($this->amount), $rate, self::INTERNAL_SCALE);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: $this->finalize($result),
            fromRate: (float) $rate,
            toRate: 1,
            amountInUzs: $this->finalize($result),
            date: $date,
        );
    }

    /**
     * Convert between two foreign currencies (cross conversion through UZS)
     *
     * The intermediate UZS amount is kept at full internal precision —
     * rounding happens only on the final values.
     */
    protected function crossConversion(string $date): ConversionResultDto
    {
        $fromRate = $this->getUnitRate($date, $this->fromCurrency->value);
        $toRate = $this->getUnitRate($date, $this->toCurrency->value);

        $amountInUzs = bcmul(self::toDecimal($this->amount), $fromRate, self::INTERNAL_SCALE);
        $result = bcdiv($amountInUzs, $toRate, self::INTERNAL_SCALE);

        return new ConversionResultDto(
            amount: $this->amount,
            fromCurrency: $this->fromCurrency->value,
            toCurrency: $this->toCurrency->value,
            result: $this->finalize($result),
            fromRate: (float) $fromRate,
            toRate: (float) $toRate,
            amountInUzs: $this->finalize($amountInUzs),
            date: $date,
        );
    }
}
