<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Optional text settings saved as-is (nullable strings).
     *
     * @var list<string>
     */
    private const OPTIONAL_KEYS = [
        'site_email',
        'site_phone',
        'site_address',
        'company_name',
        'company_tagline',
        'company_website',
        'company_registration_no',
        'china_office_address',
        'china_office_contact',
        'dhaka_office_address',
        'dhaka_office_contact',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'bank_swift_code',
        'bank_routing_number',
        'invoice_payment_terms',
        'invoice_terms',
        'invoice_signatory_name',
        'invoice_signatory_designation',
        'invoice_footer_note',
        'sales_contract_terms',
        'proforma_invoice_declaration',
    ];

    /**
     * Show the site settings form.
     */
    public function edit(): View
    {
        $settings = ['site_name' => Setting::get('site_name', 'BNoor Group')];

        foreach (self::OPTIONAL_KEYS as $key) {
            $settings[$key] = Setting::get($key);
        }

        $settings['site_logo'] = Setting::get('site_logo');

        return view('admin.settings.edit', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('site_name', $validated['site_name']);

        foreach (self::OPTIONAL_KEYS as $key) {
            Setting::set($key, $validated[$key] ?? null);
        }

        if ($request->hasFile('site_logo')) {
            Setting::set('site_logo', $request->file('site_logo')->store('logos', 'public'));
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings saved successfully.');
    }
}
