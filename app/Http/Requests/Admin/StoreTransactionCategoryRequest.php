<?php

namespace App\Http\Requests\Admin;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('transaction-categories.manage');
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_categories')->where('type', (string) $this->input('type')),
            ],
        ];
    }
}
