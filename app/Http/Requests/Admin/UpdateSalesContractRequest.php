<?php

namespace App\Http\Requests\Admin;

use App\Models\SalesContract;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales-contracts.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var SalesContract $salesContract */
        $salesContract = $this->route('salesContract');

        return [
            'contract_no' => ['required', 'string', 'max:100', Rule::unique('sales_contracts')->ignore($salesContract)],
            'buyer' => ['required', 'string', 'max:255'],
            'buyer_address' => ['nullable', 'string', 'max:500'],
            'contract_date' => ['required', 'date'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'freight_charge' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'nullable',
                Rule::exists('sales_contract_items', 'id')->where('sales_contract_id', $salesContract->id),
            ],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.hs_code' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
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
            'items.*.unit_price' => 'unit price',
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
