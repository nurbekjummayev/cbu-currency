<?php

declare(strict_types=1);

namespace Cbu\Currency\Repositories;

use Cbu\Currency\DTOs\CurrencyDto;
use Cbu\Currency\DTOs\CurrencyRateDto;
use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Exceptions\CbuApiException;
use Cbu\Currency\Helpers\CurrencyHelper;
use Cbu\Currency\Repositories\Interfaces\CurrencyRepositoryInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * API Currency Repository
 *
 * Handles all currency data fetching from the Central Bank of Uzbekistan API.
 * This repository fetches live data directly from the CBU API without database caching.
 *
 * @throws CbuApiException
 */
class ApiCurrencyRepository implements CurrencyRepositoryInterface
{
    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('cbu-currency.base_url');
        $this->timeout = (int) config('cbu-currency.timeout', 60);
    }

    /**
     * @throws CbuApiException
     */
    private function send(string $action): array
    {
        $url = $this->baseUrl.$action;

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeout)
                ->get($url);

            if (! $response->successful()) {
                throw CbuApiException::requestFailed($url, $response->status());
            }

            $data = $response->json();

            if (empty($data)) {
                throw CbuApiException::noDataReceived($url);
            }

            return $data;
        } catch (CbuApiException $e) {
            throw $e;
        } catch (Exception $e) {
            throw CbuApiException::connectionError($url, $e->getMessage());
        }
    }

    /**
     * @throws CbuApiException
     */
    public function getRatesByDate(string $date): Collection
    {
        $action = "/all/{$date}";
        $data = $this->send($action);

        return collect($data)->map(function ($item) use ($date) {
            $item['date'] = $date;

            return CurrencyRateDto::setDataFromApi($item);
        });
    }

    /**
     * @throws CbuApiException
     */
    public function getRateTodayCcy(CurrencyCcy|string $currencyCode): CurrencyRateDto
    {
        return $this->getRateByCcy(now()->format('Y-m-d'), $currencyCode);
    }

    /**
     * @throws CbuApiException
     */
    public function getTodayRateByNumericCode(int|CurrencyNumericCode $currencyCode): CurrencyRateDto
    {
        return $this->getRateByNumericCode(now()->format('Y-m-d'), $currencyCode);
    }

    /**
     * @throws CbuApiException
     */
    public function getRateByCcy(string $date, CurrencyCcy|string $currencyCode): CurrencyRateDto
    {
        $ccy = $currencyCode instanceof CurrencyCcy ? $currencyCode->value : $currencyCode;
        $action = "/{$ccy}/{$date}";
        $data = $this->send($action)[0];
        $data['date'] = $date;

        return CurrencyRateDto::setDataFromApi($data);
    }

    /**
     * @throws CbuApiException
     */
    public function getRateByNumericCode(string $date, int|CurrencyNumericCode $currencyCode): CurrencyRateDto
    {
        $numericCode = $currencyCode instanceof CurrencyNumericCode ? $currencyCode : CurrencyNumericCode::from($currencyCode);
        $ccy = $numericCode->name;

        return $this->getRateByCcy($date, $ccy);
    }

    /**
     * Get all available currencies from API
     *
     * Fetches all currencies from the API for today's date and extracts
     * unique currency information.
     *
     * @return Collection<CurrencyDto> Collection of currency DTOs
     *
     * @throws CbuApiException When API request fails
     */
    public function getAllCurrencies(): Collection
    {
        $today = now()->format('Y-m-d');
        $rates = $this->getRatesByDate($today);

        return $rates->map(fn (CurrencyRateDto $rateDto) => CurrencyDto::setDataFromRateDto($rateDto))
            ->sortBy('ccy')
            ->values();
    }

    /**
     * Get specific currency by code from API
     *
     * Fetches a single currency's information from the API for today's date.
     *
     * @param  string  $code  Currency code (e.g., USD, EUR)
     * @return CurrencyDto|null Currency DTO or null if not found
     *
     * @throws CbuApiException When API request fails
     */
    public function getCurrencyByCode(string $code): ?CurrencyDto
    {
        try {
            $today = now()->format('Y-m-d');
            $ccy = CurrencyCcy::from(strtoupper($code));
            $rateDto = $this->getRateByCcy($today, $ccy);

            return CurrencyDto::setDataFromRateDto($rateDto);
        } catch (\ValueError $e) {
            return null;
        } catch (CbuApiException $e) {
            CurrencyHelper::logError('Failed to fetch currency from API', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
