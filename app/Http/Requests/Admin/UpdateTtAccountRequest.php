<?php

namespace App\Http\Requests\Admin;

use App\Enums\TtAccountStatus;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTtAccountRequest extends FormRequest
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
            'customer_id' => ['required', Rule::exists('users', 'id')->where('type', UserType::Customer->value)],
            'title' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999999', 'max:999999999999'],
            'status' => ['required', Rule::enum(TtAccountStatus::class)],
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
        ];
    }
}
