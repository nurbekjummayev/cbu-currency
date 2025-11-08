<?php

declare(strict_types=1);

namespace Cbu\Currency\DTOs;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Models\CurrencyRate;

class CurrencyRateDto
{
    public function __construct(
        public int $cbu_id,
        public float $rate,
        public float $diff,
        public int $nominal,
        public string $date,
        public CurrencyCcy $ccy,
        public CurrencyNumericCode $code,
        public string $currency_date,
        public string $name_en,
        public string $name_uz,
        public string $name_oz,
        public string $name_ru,
    ) {}

    public function toArray(): array
    {
        return [
            'cbu_id' => $this->cbu_id,
            'rate' => $this->rate,
            'diff' => $this->diff,
            'nominal' => $this->nominal,
            'date' => $this->date,
            'ccy' => $this->ccy,
            'code' => $this->code,
            'currency_date' => $this->currency_date,
            'name_en' => $this->name_en,
            'name_uz' => $this->name_uz,
            'name_oz' => $this->name_oz,
            'name_ru' => $this->name_ru,
        ];
    }

    public static function setDataFromApi(array $data): self
    {
        return new self(
            cbu_id: (int) $data['id'],
            rate: (float) $data['Rate'],
            diff: (float) $data['Diff'],
            nominal: (int) $data['Nominal'],
            date: $data['date'],
            ccy: CurrencyCcy::from($data['Ccy']),
            code: CurrencyNumericCode::from((int) $data['Code']),
            currency_date: $data['Date'],
            name_en: $data['CcyNm_EN'] ?? '',
            name_uz: $data['CcyNm_UZ'] ?? '',
            name_oz: $data['CcyNm_UZC'] ?? '',
            name_ru: $data['CcyNm_RU'] ?? '',
        );
    }

    public static function setDataFromModel(CurrencyRate $model): self
    {
        return new self(
            cbu_id: $model->currency->cbu_id,
            rate: $model->rate,
            diff: $model->diff,
            nominal: $model->nominal,
            date: $model->date,
            ccy: $model->currency->ccy,
            code: $model->currency->code,
            currency_date: $model->currency_date,
            name_en: $model->currency->name_en,
            name_uz: $model->currency->name_uz,
            name_oz: $model->currency->name_oz,
            name_ru: $model->currency->name_ru,
        );
    }
}
