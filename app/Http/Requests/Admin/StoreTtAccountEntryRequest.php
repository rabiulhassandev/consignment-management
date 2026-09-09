<?php

namespace App\Http\Requests\Admin;

use App\Enums\ConversionOperation;
use App\Enums\EntryType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTtAccountEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('tt-accounts.edit');
    }

    /**
     * Fall back to multiplying the source amount by the rate.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('source_operation'))) {
            $this->merge(['source_operation' => ConversionOperation::Multiply->value]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entry_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EntryType::class)],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'source_currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'source_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'source_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'source_operation' => ['required', Rule::enum(ConversionOperation::class)],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'entry_date' => 'date',
            'source_currency_id' => 'source currency',
            'source_amount' => 'source amount',
            'source_rate' => 'source rate',
            'source_operation' => 'conversion operation',
        ];
    }
}
