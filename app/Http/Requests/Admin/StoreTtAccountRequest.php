<?php

namespace App\Http\Requests\Admin;

use App\Enums\TtAccountStatus;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTtAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Opening the account for a brand new customer also creates that customer,
     * so it additionally requires the customer creation permission.
     */
    public function authorize(): bool
    {
        if (! $this->user()->can('tt-accounts.create')) {
            return false;
        }

        return ! $this->createsCustomer() || $this->user()->can('customers.create');
    }

    /**
     * Default to picking an existing customer.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('customer_mode'))) {
            $this->merge(['customer_mode' => 'existing']);
        }
    }

    /**
     * Whether the request opens the account for a customer that does not exist yet.
     */
    public function createsCustomer(): bool
    {
        return $this->input('customer_mode') === 'new';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => [
                'exclude_if:customer_mode,new',
                'required',
                Rule::exists('users', 'id')->where('type', UserType::Customer->value),
            ],
            'new_customer_name' => ['exclude_unless:customer_mode,new', 'required', 'string', 'max:255'],
            'new_customer_email' => [
                'exclude_unless:customer_mode,new',
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email'),
            ],
            'new_customer_password' => ['exclude_unless:customer_mode,new', 'required', Password::defaults()],
            'title' => ['required', 'string', 'max:255'],
            'currency_id' => ['required', Rule::exists('currencies', 'id')->where('is_active', true)],
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
            'new_customer_name' => 'customer name',
            'new_customer_email' => 'customer email',
            'new_customer_password' => 'customer password',
        ];
    }
}
