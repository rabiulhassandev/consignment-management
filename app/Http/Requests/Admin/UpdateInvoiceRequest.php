<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('invoices.edit');
    }

    /**
     * Treat a blank collection box as nothing collected.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('received_amount'))) {
            $this->merge(['received_amount' => 0]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return [
            'customer_id' => ['required', Rule::exists('users', 'id')->where('type', UserType::Customer->value)],
            'received_amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'bill_to_address' => ['nullable', 'string', 'max:500'],
            'invoice_date' => ['required', 'date'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:50'],
            'bank_routing_number' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'nullable',
                Rule::exists('invoice_items', 'id')->where('invoice_id', $invoice->id),
            ],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
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
            'customer_id' => 'customer',
            'items.*.description' => 'description',
            'items.*.quantity' => 'quantity',
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
