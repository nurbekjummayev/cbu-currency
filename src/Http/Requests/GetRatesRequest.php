<?php

declare(strict_types=1);

namespace Cbu\Currency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Get Currency Rates Request
 *
 * Validates request for fetching currency rates by date.
 */
class GetRatesRequest extends FormRequest
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
}
