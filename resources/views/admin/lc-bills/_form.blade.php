@php
    use App\Enums\EntryType;

    /** @var \App\Models\LcBill|null $lcBill */
    $lcBill = $lcBill ?? null;

    $emptyEntry = [
        'id' => null,
        'entry_date' => '',
        'description' => '',
        'source_amount' => '',
        'source_rate' => '',
        'amount' => '',
    ];

    $mapEntries = fn ($entries) => $entries->map(fn ($entry) => [
        'id' => $entry->id,
        'entry_date' => $entry->entry_date?->format('Y-m-d') ?? '',
        'description' => $entry->description,
        'source_amount' => $entry->source_amount !== null ? (string) $entry->source_amount : '',
        'source_rate' => $entry->source_rate !== null ? (string) $entry->source_rate : '',
        'amount' => (string) $entry->amount,
    ])->values()->all();

    $initialReceipts = old('receipts') ?? ($lcBill ? $mapEntries($lcBill->entries->where('type', EntryType::Received)) : []);
    $initialPayments = old('payments') ?? ($lcBill ? $mapEntries($lcBill->entries->where('type', EntryType::Paid)) : []);

    $receiptErrors = collect($errors->get('receipts.*'))->flatten()->unique();
    $paymentErrors = collect($errors->get('payments.*'))->flatten()->unique();
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          receipts: {{ Js::from($initialReceipts) }},
          payments: {{ Js::from($initialPayments) }},
          emptyEntry: {{ Js::from($emptyEntry) }},
          rate: {{ Js::from((string) old('conversion_rate', $lcBill?->conversion_rate ?? '')) }},
          add(side) { this[side].push({ ...this.emptyEntry }) },
          remove(side, index) { this[side].splice(index, 1) },
          convert(entry) {
              const amount = parseFloat(entry.source_amount);
              const rate = parseFloat(entry.source_rate);
              if (!isNaN(amount) && rate > 0) { entry.amount = (amount / rate).toFixed(2) }
          },
          sum(side) { return this[side].reduce((sum, entry) => sum + (parseFloat(entry.amount) || 0), 0) },
          get balance() { return this.sum('receipts') - this.sum('payments') },
          get localDue() { return this.balance * (parseFloat(this.rate) || 0) },
          money(value) { return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
      }">
    @csrf
    @if ($lcBill)
        @method('PUT')
    @endif

    @if ($receiptErrors->isNotEmpty() || $paymentErrors->isNotEmpty())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please fix the entries below:</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($receiptErrors as $message)
                    <li>Received: {{ $message }}</li>
                @endforeach
                @foreach ($paymentErrors as $message)
                    <li>Paid: {{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card title="Bill Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-form.input name="bill_no" label="Bill number"
                          :value="$lcBill?->bill_no ?? $suggestedNumber ?? ''" required />
            <x-form.select name="customer_id" label="Customer" placeholder="Select customer" required>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}"
                            @selected((int) old('customer_id', $lcBill?->customer_id ?? $preselectedCustomerId ?? 0) === $customerOption->id)>
                        {{ $customerOption->name }}
                    </option>
                @endforeach
            </x-form.select>
            <x-form.input name="bill_date" type="date" label="Bill date"
                          :value="$lcBill?->bill_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.input name="lc_number" label="LC number"
                          :value="$lcBill?->lc_number ?? ''" placeholder="e.g. 350626010291" required />
            <x-form.input name="lc_value" type="number" step="0.01" min="0" label="LC value"
                          :value="$lcBill?->lc_value ?? ''" placeholder="0.00" />
            <x-form.input name="ci_value" type="number" step="0.01" min="0" label="CI value"
                          :value="$lcBill?->ci_value ?? ''" placeholder="0.00" />
            <x-form.input name="shipment_title" label="Shipment"
                          :value="$lcBill?->shipment_title ?? ''" placeholder="e.g. 20 GP CONTAINER TO CHITTAGONG" class="sm:col-span-2" />
            <x-form.select name="currency_id" label="Bill currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $lcBill?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
            <x-form.input name="conversion_rate" type="number" step="0.0001" min="0" label="Bank rate (to BDT)"
                          placeholder="e.g. 124" x-model="rate" />
            <label class="flex items-center gap-2 pt-7 text-sm font-medium text-gray-700">
                <input type="checkbox" name="is_settled" value="1" @checked(old('is_settled', $lcBill?->is_settled))
                       class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                Mark as settled
            </label>
        </div>
    </x-card>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        @include('admin.lc-bills._entries', [
            'side' => 'receipts',
            'title' => 'Received',
            'hint' => 'Money received from the customer for this LC.',
        ])
        @include('admin.lc-bills._entries', [
            'side' => 'payments',
            'title' => 'Paid / Expenses',
            'hint' => 'Freight, handling, and other expenses paid for this LC.',
        ])
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-xs">
        <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
            <div>
                <p class="text-gray-500">Total Received</p>
                <p class="mt-1 text-lg font-semibold text-emerald-600" x-text="money(sum('receipts'))"></p>
            </div>
            <div>
                <p class="text-gray-500">Total Paid</p>
                <p class="mt-1 text-lg font-semibold text-rose-600" x-text="money(sum('payments'))"></p>
            </div>
            <div>
                <p class="text-gray-500">Balance</p>
                <p class="mt-1 text-lg font-semibold text-gray-900" x-text="money(balance)"></p>
            </div>
            <div>
                <p class="text-gray-500">Due (BDT)</p>
                <p class="mt-1 text-lg font-semibold text-gray-900" x-text="rate ? money(localDue) : '—'"></p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="route('admin.lc-bills.index')">Cancel</x-button>
        <x-button type="submit">{{ $lcBill ? 'Save Changes' : 'Create LC Bill' }}</x-button>
    </div>
</form>
