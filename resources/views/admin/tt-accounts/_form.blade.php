@php
    /** @var \App\Models\TtAccount|null $ttAccount */
    $ttAccount = $ttAccount ?? null;
    $canCreateCustomer = ! $ttAccount && auth()->user()->can('customers.create');
    $customerMode = old('customer_mode', 'existing');
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{ customerMode: {{ Js::from($canCreateCustomer ? $customerMode : 'existing') }} }">
    @csrf
    @if ($ttAccount)
        @method('PUT')
    @endif

    <x-card title="Account Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="title" label="Account title" class="sm:col-span-2"
                          :value="$ttAccount?->title ?? ''" placeholder="e.g. SPF CHINA TT ACCOUNTS {{ now()->year }}" required />

            @if ($canCreateCustomer)
                <div class="sm:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700">Customer</span>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="customer_mode" value="existing" x-model="customerMode"
                                   class="size-4 border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            Existing customer
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="customer_mode" value="new" x-model="customerMode"
                                   class="size-4 border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            New customer
                        </label>
                    </div>
                    @error('customer_mode')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @elseif (! $ttAccount)
                <input type="hidden" name="customer_mode" value="existing">
            @endif

            <div x-show="customerMode === 'existing'" class="sm:col-span-1">
                <x-form.select name="customer_id" label="Customer" placeholder="Select customer" required
                               x-bind:required="customerMode === 'existing'">
                    @foreach ($customers as $customerOption)
                        <option value="{{ $customerOption->id }}"
                                @selected((int) old('customer_id', $ttAccount?->customer_id ?? $preselectedCustomerId ?? 0) === $customerOption->id)>
                            {{ $customerOption->name }}
                        </option>
                    @endforeach
                </x-form.select>
            </div>

            <x-form.select name="currency_id" label="Account currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $ttAccount?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
            <x-form.input name="opening_balance" type="number" step="0.01" label="Opening balance"
                          :value="$ttAccount?->opening_balance ?? ''" placeholder="0.00" />
            <x-form.select name="status" label="Status" required>
                @foreach (\App\Enums\TtAccountStatus::cases() as $statusOption)
                    <option value="{{ $statusOption->value }}"
                            @selected(old('status', $ttAccount?->status->value ?? \App\Enums\TtAccountStatus::Open->value) === $statusOption->value)>
                        {{ $statusOption->label() }}
                    </option>
                @endforeach
            </x-form.select>
        </div>

        @if ($canCreateCustomer)
            <div x-show="customerMode === 'new'" x-cloak class="mt-6 rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                <p class="text-sm font-semibold text-gray-700">New Customer</p>
                <p class="mt-0.5 mb-4 text-xs text-gray-500">
                    The customer is added to the customer list and signs in with the email and password you set here.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-form.input name="new_customer_name" label="Full name" placeholder="e.g. MIL Bangladesh" required
                                  x-bind:required="customerMode === 'new'" />
                    <x-form.input name="new_customer_email" type="email" label="Email address" autocomplete="off" required
                                  x-bind:required="customerMode === 'new'" />
                    <x-form.input name="new_customer_password" type="password" label="Password" autocomplete="new-password" required
                                  x-bind:required="customerMode === 'new'" />
                </div>
            </div>
        @endif
    </x-card>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="$ttAccount ? route('admin.tt-accounts.show', $ttAccount) : route('admin.tt-accounts.index')">Cancel</x-button>
        <x-button type="submit">{{ $ttAccount ? 'Save Changes' : 'Create TT Account' }}</x-button>
    </div>
</form>
