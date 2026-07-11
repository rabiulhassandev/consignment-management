<?php

namespace App\Http\Requests\Admin;

use App\Models\Consignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('consignments.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Consignment $consignment */
        $consignment = $this->route('consignment');

        return [
            'consignment_no' => ['required', 'string', 'max:100', Rule::unique('consignments')->ignore($consignment)],
            'consignment_date' => ['required', 'date'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'nullable',
                Rule::exists('purchase_items', 'id')->where('consignment_id', $consignment->id),
            ],
            'items.*.purchase_date' => ['required', 'date'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.category_id' => ['required', Rule::exists('categories', 'id')],
            'items.*.supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('customer_id', $consignment->customer_id),
            ],
            'items.*.sample_number' => ['nullable', 'string', 'max:100'],
            'items.*.own_sample_number' => ['nullable', 'string', 'max:100'],
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
            'items.*.purchase_date' => 'purchase date',
            'items.*.product_name' => 'product name',
            'items.*.category_id' => 'category',
            'items.*.supplier_id' => 'supplier',
            'items.*.sample_number' => 'sample number',
            'items.*.own_sample_number' => 'own sample number',
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
            'items.required' => 'Add at least one purchase item.',
            'items.min' => 'Add at least one purchase item.',
        ];
    }
}
