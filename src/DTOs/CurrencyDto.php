<?php

declare(strict_types=1);

namespace Cbu\Currency\DTOs;

use Cbu\Currency\Enums\CurrencyCcy;
use Cbu\Currency\Enums\CurrencyNumericCode;
use Cbu\Currency\Models\Currency;

/**
 * Currency Data Transfer Object
 *
 * Represents currency information from either API or Database.
 */
class CurrencyDto
{
    public function __construct(
        public ?int $id,
        public string $cbu_id,
        public CurrencyNumericCode $code,
        public CurrencyCcy $ccy,
        public string $name_uz,
        public string $name_oz,
        public string $name_ru,
        public string $name_en,
    ) {}

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cbu_id' => $this->cbu_id,
            'code' => $this->code->value,
            'ccy' => $this->ccy->value,
            'name_uz' => $this->name_uz,
            'name_oz' => $this->name_oz,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
        ];
    }

    /**
     * Create DTO from Currency model
     */
    public static function setDataFromModel(Currency $model): self
    {
        return new self(
            id: $model->id,
            cbu_id: $model->cbu_id,
            code: $model->code,
            ccy: $model->ccy,
            name_uz: $model->name_uz,
            name_oz: $model->name_oz,
            name_ru: $model->name_ru,
            name_en: $model->name_en,
        );
    }

    /**
     * Create DTO from CurrencyRateDto (used when fetching from API)
     */
    public static function setDataFromRateDto(CurrencyRateDto $rateDto): self
    {
        return new self(
            id: null,
            cbu_id: (string) $rateDto->cbu_id,
            code: $rateDto->code,
            ccy: $rateDto->ccy,
            name_uz: $rateDto->name_uz,
            name_oz: $rateDto->name_oz,
            name_ru: $rateDto->name_ru,
            name_en: $rateDto->name_en,
        );
    }
}
