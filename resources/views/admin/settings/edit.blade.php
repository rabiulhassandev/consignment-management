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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="company_tagline" label="Tagline"
                                  :value="$settings['company_tagline']" placeholder="e.g. Global Sourcing & Freight Forwarding" />
                    <x-form.input name="company_website" label="Website"
                                  :value="$settings['company_website']" placeholder="e.g. www.bnoorgroup.com" />
                </div>

                <x-form.input name="company_registration_no" label="Business registration no. (BIN / TIN)"
                              :value="$settings['company_registration_no']" placeholder="Printed under the company name on every document" />

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
                    <x-form.input name="bank_swift_code" label="SWIFT / BIC code"
                                  :value="$settings['bank_swift_code']" placeholder="For incoming TT from abroad" />
                    <x-form.input name="bank_routing_number" label="Routing number"
                                  :value="$settings['bank_routing_number']" placeholder="For local BEFTN transfers" />
                </div>

                <x-form.input name="invoice_payment_terms" label="Payment terms"
                              :value="$settings['invoice_payment_terms']" placeholder="e.g. Payment due within 15 days of invoice date" />

                <x-form.textarea name="invoice_terms" label="Invoice terms &amp; conditions"
                                 :value="$settings['invoice_terms']" rows="4"
                                 placeholder="One condition per line. Shown above the signature on printed invoices." />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="invoice_signatory_name" label="Signatory name"
                                  :value="$settings['invoice_signatory_name']" placeholder="Printed under the signature line" />
                    <x-form.input name="invoice_signatory_designation" label="Signatory designation"
                                  :value="$settings['invoice_signatory_designation']" placeholder="e.g. Managing Director" />
                </div>

                <x-form.input name="invoice_footer_note" label="Invoice footer note"
                              :value="$settings['invoice_footer_note']" placeholder="Closing line at the very bottom of printed invoices" />
            </div>
        </x-card>

        <div class="flex items-center justify-end">
            <x-button type="submit">Save Settings</x-button>
        </div>
    </form>
</x-admin-layout>
