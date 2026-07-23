<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_email' => ['nullable', 'string', 'email', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:50'],
            'site_address' => ['nullable', 'string', 'max:500'],
            'site_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_tagline' => ['nullable', 'string', 'max:255'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_registration_no' => ['nullable', 'string', 'max:100'],
            'china_office_address' => ['nullable', 'string', 'max:500'],
            'china_office_contact' => ['nullable', 'string', 'max:255'],
            'dhaka_office_address' => ['nullable', 'string', 'max:500'],
            'dhaka_office_contact' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:50'],
            'bank_routing_number' => ['nullable', 'string', 'max:50'],
            'invoice_payment_terms' => ['nullable', 'string', 'max:255'],
            'invoice_terms' => ['nullable', 'string', 'max:2000'],
            'invoice_signatory_name' => ['nullable', 'string', 'max:255'],
            'invoice_signatory_designation' => ['nullable', 'string', 'max:255'],
            'invoice_footer_note' => ['nullable', 'string', 'max:500'],
            'sales_contract_terms' => ['nullable', 'string', 'max:2000'],
            'proforma_invoice_declaration' => ['nullable', 'string', 'max:500'],
        ];
    }
}
