<?php

namespace App\Http\Requests\Admin;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('transactions.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'transaction_category_id' => [
                'required',
                Rule::exists('transaction_categories', 'id')->where('type', (string) $this->input('type')),
            ],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'description' => ['nullable', 'string', 'max:500'],
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
            'transaction_category_id' => 'category',
            'transaction_date' => 'date',
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
            'transaction_category_id.exists' => 'The selected category does not match the entry type.',
        ];
    }
}
