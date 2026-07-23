<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProformaInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('proforma-invoices.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_no' => ['required', 'string', 'max:100', Rule::unique('proforma_invoices')],
            'invoice_date' => ['required', 'date'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('is_active', true)],
            'exporter_name' => ['nullable', 'string', 'max:255'],
            'exporter_address' => ['nullable', 'string', 'max:500'],
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_address' => ['nullable', 'string', 'max:500'],
            'advising_bank_name' => ['nullable', 'string', 'max:255'],
            'advising_bank_address' => ['nullable', 'string', 'max:500'],
            'advising_bank_swift' => ['nullable', 'string', 'max:50'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_account' => ['nullable', 'string', 'max:100'],
            'pre_carriage' => ['nullable', 'string', 'max:150'],
            'place_of_receipt' => ['nullable', 'string', 'max:150'],
            'country_of_origin' => ['nullable', 'string', 'max:150'],
            'port_of_loading' => ['nullable', 'string', 'max:150'],
            'port_of_discharge' => ['nullable', 'string', 'max:150'],
            'final_destination' => ['nullable', 'string', 'max:150'],
            'delivery_payment_terms' => ['nullable', 'string', 'max:255'],
            'incoterm' => ['nullable', 'string', 'max:20'],
            'mark' => ['nullable', 'string', 'max:100'],
            'declaration' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.hs_code' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
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
            'items.*.description' => 'description',
            'items.*.hs_code' => 'H.S. code',
            'items.*.quantity' => 'quantity',
            'items.*.unit' => 'unit',
            'items.*.rate' => 'rate',
            'items.*.amount' => 'amount',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Add at least one item.',
            'items.min' => 'Add at least one item.',
        ];
    }
}
