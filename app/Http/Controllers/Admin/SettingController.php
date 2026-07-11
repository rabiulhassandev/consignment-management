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
     * Show the site settings form.
     */
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => [
                'site_name' => Setting::get('site_name', 'BNoor Group'),
                'site_email' => Setting::get('site_email'),
                'site_phone' => Setting::get('site_phone'),
                'site_address' => Setting::get('site_address'),
                'site_logo' => Setting::get('site_logo'),
            ],
        ]);
    }

    /**
     * Update the site settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Setting::set('site_name', $validated['site_name']);
        Setting::set('site_email', $validated['site_email'] ?? null);
        Setting::set('site_phone', $validated['site_phone'] ?? null);
        Setting::set('site_address', $validated['site_address'] ?? null);

        if ($request->hasFile('site_logo')) {
            Setting::set('site_logo', $request->file('site_logo')->store('logos', 'public'));
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('success', 'Settings saved successfully.');
    }
}
