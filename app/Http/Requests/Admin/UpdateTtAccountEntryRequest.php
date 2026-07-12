<?php

namespace App\Http\Requests\Admin;

use App\Enums\EntryType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTtAccountEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('tt-accounts.edit');
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
        ];
    }
}
