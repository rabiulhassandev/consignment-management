<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use App\Models\LcBill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLcBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('lc-bills.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var LcBill $lcBill */
        $lcBill = $this->route('lcBill');

        $entryIdRule = Rule::exists('lc_bill_entries', 'id')->where('lc_bill_id', $lcBill->id);

        return [
            'customer_id' => ['required', Rule::exists('users', 'id')->where('type', UserType::Customer->value)],
            'bill_no' => ['required', 'string', 'max:100', Rule::unique('lc_bills')->ignore($lcBill)],
            'bill_date' => ['required', 'date'],
            'lc_number' => ['required', 'string', 'max:100'],
            'lc_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'ci_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'shipment_title' => ['nullable', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'conversion_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'is_settled' => ['nullable', 'boolean'],
            'receipts' => ['nullable', 'array'],
            'receipts.*.id' => ['nullable', $entryIdRule],
            'receipts.*.entry_date' => ['nullable', 'date'],
            'receipts.*.description' => ['required', 'string', 'max:255'],
            'receipts.*.source_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'receipts.*.source_rate' => ['nullable', 'numeric', 'gt:0', 'max:999999'],
            'receipts.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'payments' => ['nullable', 'array'],
            'payments.*.id' => ['nullable', $entryIdRule],
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
