<?php

namespace App\Http\Requests\Admin;

use App\Enums\ConversionOperation;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLcBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('lc-bills.create');
    }

    /**
     * Fall back to multiplication when no conversion operation is submitted.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('conversion_operation'))) {
            $this->merge(['conversion_operation' => ConversionOperation::Multiply->value]);
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
            'customer_id' => ['required', Rule::exists('users', 'id')->where('type', UserType::Customer->value)],
            'bill_no' => ['required', 'string', 'max:100', Rule::unique('lc_bills')],
            'bill_date' => ['required', 'date'],
            'lc_number' => ['required', 'string', 'max:100'],
            'lc_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'ci_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'shipment_title' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('is_active', true)],
            'conversion_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'conversion_currency_id' => ['nullable', 'required_with:conversion_rate', Rule::exists('currencies', 'id')->where('is_active', true)],
            'conversion_operation' => ['required', Rule::enum(ConversionOperation::class)],
            'is_settled' => ['nullable', 'boolean'],
            'receipts' => ['nullable', 'array'],
            'receipts.*.entry_date' => ['nullable', 'date'],
            'receipts.*.description' => ['required', 'string', 'max:255'],
            'receipts.*.source_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'receipts.*.source_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'receipts.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'payments' => ['nullable', 'array'],
            'payments.*.entry_date' => ['nullable', 'date'],
            'payments.*.description' => ['required', 'string', 'max:255'],
            'payments.*.source_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'payments.*.source_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'payments.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
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
            'customer_id' => 'customer',
            'conversion_currency_id' => 'conversion currency',
            'conversion_operation' => 'conversion operation',
            'receipts.*.entry_date' => 'date',
            'receipts.*.description' => 'description',
            'receipts.*.source_amount' => 'source amount',
            'receipts.*.source_rate' => 'source rate',
            'receipts.*.amount' => 'amount',
            'payments.*.entry_date' => 'date',
            'payments.*.description' => 'description',
            'payments.*.source_amount' => 'source amount',
            'payments.*.source_rate' => 'source rate',
            'payments.*.amount' => 'amount',
        ];
    }
}
