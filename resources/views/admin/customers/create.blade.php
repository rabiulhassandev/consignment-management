<x-admin-layout title="Add Customer">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Add Customer</h1>
        <p class="mt-1 text-sm text-gray-500">Create a customer account with login credentials.</p>
    </div>

    <x-card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="name" label="Full name" required />
                <x-form.input name="email" type="email" label="Email address" required />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="phone" label="Phone" />
                <x-form.input name="company_name" label="Company name" />
            </div>

            <x-form.input name="address" label="Address" />

            <x-form.select name="status" label="Account status" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}"
                            @selected(old('status', \App\Enums\UserStatus::Approved->value) === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </x-form.select>

            <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                <p class="text-sm font-semibold text-gray-700">Login Credentials</p>
                <p class="mt-0.5 mb-4 text-xs text-gray-500">
                    Share these with the customer — they sign in with the email address above.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" />
                    <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button variant="secondary" :href="route('admin.customers.index')">Cancel</x-button>
                <x-button type="submit">Add Customer</x-button>
            </div>
        </form>
    </x-card>
</x-admin-layout>
