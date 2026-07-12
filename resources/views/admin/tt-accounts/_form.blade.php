@php
    /** @var \App\Models\TtAccount|null $ttAccount */
    $ttAccount = $ttAccount ?? null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($ttAccount)
        @method('PUT')
    @endif

    <x-card title="Account Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.input name="title" label="Account title" class="sm:col-span-2"
                          :value="$ttAccount?->title ?? ''" placeholder="e.g. SPF CHINA TT ACCOUNTS {{ now()->year }}" required />
            <x-form.select name="customer_id" label="Customer" placeholder="Select customer" required>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}"
                            @selected((int) old('customer_id', $ttAccount?->customer_id ?? $preselectedCustomerId ?? 0) === $customerOption->id)>
                        {{ $customerOption->name }}
                    </option>
                @endforeach
            </x-form.select>
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
    </x-card>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="$ttAccount ? route('admin.tt-accounts.show', $ttAccount) : route('admin.tt-accounts.index')">Cancel</x-button>
        <x-button type="submit">{{ $ttAccount ? 'Save Changes' : 'Create TT Account' }}</x-button>
    </div>
</form>
