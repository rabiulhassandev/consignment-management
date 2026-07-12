<x-admin-layout title="Settings">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Settings</h1>
        <p class="mt-1 text-sm text-gray-500">Global site information used across the application.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <x-card title="Site Information">
            <div class="space-y-4">
                <x-form.input name="site_name" label="Site name" :value="$settings['site_name']" required />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="site_email" type="email" label="Contact email" :value="$settings['site_email']" />
                    <x-form.input name="site_phone" label="Contact phone" :value="$settings['site_phone']" />
                </div>

                <x-form.input name="site_address" label="Address" :value="$settings['site_address']" />

                <div>
                    <label for="site_logo" class="mb-1.5 block text-sm font-medium text-gray-700">Logo</label>
                    @if ($settings['site_logo'])
                        <img src="{{ Storage::url($settings['site_logo']) }}" alt="Site logo" class="mb-2 h-12 w-auto rounded">
                    @endif
                    <input type="file" name="site_logo" id="site_logo" accept="image/*"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    @error('site_logo')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-card>

        <x-card title="Billing Documents">
            <p class="mb-4 text-sm text-gray-500">
                Company letterhead and payment details shown on printed invoices, LC bills, and TT account statements.
            </p>

            <div class="space-y-4">
                <x-form.input name="company_name" label="Company name (on documents)"
                              :value="$settings['company_name']" placeholder="Full registered company name" />
                <x-form.input name="company_tagline" label="Tagline"
                              :value="$settings['company_tagline']" placeholder="e.g. Global Sourcing & Freight Forwarding" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.textarea name="china_office_address" label="China office address"
                                     :value="$settings['china_office_address']" rows="2" />
                    <x-form.textarea name="dhaka_office_address" label="Dhaka office address"
                                     :value="$settings['dhaka_office_address']" rows="2" />
                    <x-form.input name="china_office_contact" label="China office contact"
                                  :value="$settings['china_office_contact']" placeholder="Name · Phone" />
                    <x-form.input name="dhaka_office_contact" label="Dhaka office contact"
                                  :value="$settings['dhaka_office_contact']" placeholder="Name · Phone" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="bank_name" label="Bank name" :value="$settings['bank_name']" />
                    <x-form.input name="bank_account_name" label="Account name" :value="$settings['bank_account_name']" />
                    <x-form.input name="bank_account_number" label="Account number" :value="$settings['bank_account_number']" />
                    <x-form.input name="bank_branch" label="Branch" :value="$settings['bank_branch']" />
                </div>

                <x-form.input name="invoice_footer_note" label="Invoice footer note"
                              :value="$settings['invoice_footer_note']" placeholder="Shown at the bottom of printed invoices" />
            </div>
        </x-card>

        <div class="flex items-center justify-end">
            <x-button type="submit">Save Settings</x-button>
        </div>
    </form>
</x-admin-layout>
