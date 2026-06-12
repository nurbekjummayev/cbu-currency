<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Requests;

use Cbu\Currency\Enums\CurrencyCcy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Convert Currency Request
 *
 * Validates request for currency conversion.
 */
class ConvertCurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currencyCodes = array_column(CurrencyCcy::cases(), 'value');

        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from' => ['required', 'string', Rule::in($currencyCodes)],
            'to' => ['required', 'string', Rule::in($currencyCodes)],
            'date' => ['nullable', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
            'scale' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.01.',
            'from.required' => 'The source currency code is required.',
            'from.in' => 'The source currency must be a valid ISO 4217 code (e.g., USD, EUR, RUB).',
            'to.required' => 'The target currency code is required.',
            'to.in' => 'The target currency must be a valid ISO 4217 code (e.g., USD, EUR, RUB).',
            'date.date' => 'The date must be a valid date.',
            'date.date_format' => 'The date must be in Y-m-d format (e.g., 2025-01-15).',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'scale.integer' => 'The scale must be an integer.',
            'scale.min' => 'The scale must be at least 0.',
            'scale.max' => 'The scale may not be greater than 20.',
        ];
    }

    /**
     * Get validated amount
     */
    public function getAmount(): float
    {
        return (float) $this->validated('amount');
    }

    /**
     * Get validated source currency
     */
    public function getFrom(): string
    {
        return strtoupper($this->validated('from'));
    }

    /**
     * Get validated target currency
     */
    public function getTo(): string
    {
        return strtoupper($this->validated('to'));
    }

    /**
     * Get validated date or default to today
     */
    public function getDate(): string
    {
        return $this->validated('date') ?? now()->format('Y-m-d');
    }

    /**
     * Get validated scale or null when not provided (no rounding)
     */
    public function getScale(): ?int
    {
        $scale = $this->validated('scale');

        return $scale !== null ? (int) $scale : null;
    }
}
