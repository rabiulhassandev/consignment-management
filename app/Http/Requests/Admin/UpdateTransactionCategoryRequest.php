<?php

namespace App\Http\Requests\Admin;

use App\Models\TransactionCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionCategoryRequest extends FormRequest
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
        /** @var TransactionCategory $category */
        $category = $this->route('transaction_category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_categories')
                    ->where('type', $category->type->value)
                    ->ignore($category),
            ],
        ];
    }
}
