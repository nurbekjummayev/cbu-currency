<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Requests;

use Cbu\Currency\Enums\CurrencyCcy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Get Currency Rate by Code Request
 *
 * Validates request for fetching a specific currency rate by code and date.
 */
class GetRateByCcyRequest extends FormRequest
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
        return [
            'ccy' => ['required', 'string', Rule::enum(CurrencyCcy::class)],
            'date' => ['nullable', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
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
            'ccy.required' => 'The currency code is required.',
            'ccy.in' => 'The currency code must be a valid ISO 4217 code (e.g., USD, EUR, RUB).',
            'date.date' => 'The date must be a valid date.',
            'date.date_format' => 'The date must be in Y-m-d format (e.g., 2025-01-15).',
            'date.before_or_equal' => 'The date cannot be in the future.',
        ];
    }

    /**
     * Get validated date or default to today
     */
    public function getDate(): string
    {
        return $this->validated('date') ?? now()->format('Y-m-d');
    }

    /**
     * Get validated currency code
     */
    public function getCcy(): string
    {
        return strtoupper($this->validated('ccy'));
    }
}
