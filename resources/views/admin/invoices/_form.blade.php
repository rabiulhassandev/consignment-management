@php
    /** @var \App\Models\Invoice|null $invoice */
    $invoice = $invoice ?? null;

    $emptyItem = [
        'id' => null,
        'description' => '',
        'quantity' => '',
        'rate' => '',
        'amount' => '',
    ];

    $initialItems = old('items') ?? $invoice?->items->map(fn ($item) => [
        'id' => $item->id,
        'description' => $item->description,
        'quantity' => $item->quantity !== null ? (string) $item->quantity : '',
        'rate' => $item->rate !== null ? (string) $item->rate : '',
        'amount' => (string) $item->amount,
    ])->values()->all() ?? [$emptyItem];

    $itemErrors = collect($errors->get('items.*'))->flatten()->unique();

    $defaultCustomerId = $invoice?->customer_id ?? ($preselectedCustomerId ?? null);
    $selectedCustomerId = (string) (old('customer_id', $defaultCustomerId) ?: '');
    $customerAddresses = $customers->pluck('address', 'id');
    $selectedCurrencyId = (string) (old('currency_id', $invoice?->currency_id) ?: '');
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          items: {{ Js::from($initialItems) }},
          emptyItem: {{ Js::from($emptyItem) }},
          customerId: {{ Js::from($selectedCustomerId) }},
          currencyId: {{ Js::from($selectedCurrencyId) }},
          billToAddress: {{ Js::from((string) old('bill_to_address', $invoice?->bill_to_address ?? '')) }},
          customerAddresses: {{ Js::from($customerAddresses) }},
          received: {{ Js::from((string) old('received_amount', $invoice?->received_amount ?? '0')) }},
          walletBalances: {{ Js::from($walletBalances) }},
          currencyCodes: {{ Js::from($currencies->pluck('code', 'id')) }},
          applyCustomerAddress() { this.billToAddress = this.customerAddresses[this.customerId] ?? '' },
          get currencyCode() { return this.currencyCodes[this.currencyId] ?? '' },
          get walletBalance() { return (this.walletBalances[this.customerId] ?? {})[this.currencyId] ?? 0 },
          get otherWalletBalances() {
              return Object.entries(this.walletBalances[this.customerId] ?? {})
                  .filter(([id, balance]) => id !== String(this.currencyId) && Math.abs(balance) >= 0.005)
                  .map(([id, balance]) => ({ code: this.currencyCodes[id] ?? '', balance }));
          },
          get walletAfter() { return this.walletBalance + (parseFloat(this.received) || 0) - this.total },
          addItem() { this.items.push({ ...this.emptyItem }) },
          removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1) } },
          recalc(item) {
              const quantity = parseFloat(item.quantity);
              const rate = parseFloat(item.rate);
              if (!isNaN(quantity) && !isNaN(rate)) { item.amount = (quantity * rate).toFixed(2) }
          },
          get total() { return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0) },
          money(value) { return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
      }">
    @csrf
    @if ($invoice)
        @method('PUT')
    @endif

    @if ($itemErrors->isNotEmpty() || $errors->has('items'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please fix the invoice items below:</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->get('items') as $message)
                    <li>{{ $message }}</li>
                @endforeach
                @foreach ($itemErrors as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card title="Invoice Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="invoice_no" class="mb-1.5 block text-sm font-medium text-gray-700">Invoice number</label>
                <input type="text" id="invoice_no" readonly
                       value="{{ $invoice?->invoice_no ?? $nextNumber ?? '' }}"
                       class="block w-full cursor-not-allowed rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-500 shadow-sm ring-1 ring-inset ring-gray-200">
                <p class="mt-1.5 text-xs text-gray-400">
                    {{ $invoice ? 'Fixed once the invoice is created.' : 'Generated automatically when the invoice is saved.' }}
                </p>
            </div>
            <x-form.select name="customer_id" label="Customer" placeholder="Select customer" required
                           x-model="customerId" @change="applyCustomerAddress()">
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}" @selected($selectedCustomerId === (string) $customerOption->id)>
                        {{ $customerOption->name }}
                    </option>
                @endforeach
            </x-form.select>
            <x-form.input name="invoice_date" type="date" label="Invoice date"
                          :value="$invoice?->invoice_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.select name="currency_id" label="Currency" placeholder="Select currency" required
                           x-model="currencyId">
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $invoice?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
        </div>

        <div class="mt-4">
            <x-form.textarea name="bill_to_address" label="Billing address" rows="2" x-model="billToAddress"
                             placeholder="Prefilled from the customer — edit it to override on this invoice" />
        </div>
    </x-card>

    {{-- Customer wallet: visible once a customer is chosen --}}
    <div class="mt-6" x-cloak x-show="customerId">
        <x-card title="Customer Wallet">
            <x-slot:actions>
                <span class="text-xs text-gray-400">Balance in <span class="font-semibold" x-text="currencyCode || '—'"></span></span>
            </x-slot:actions>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-gray-50/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Current wallet balance</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums"
                       :class="walletBalance < 0 ? 'text-rose-600' : 'text-emerald-600'"
                       x-text="money(walletBalance) + ' ' + currencyCode"></p>
                    <p class="mt-1 text-xs text-gray-500"
                       x-text="walletBalance < 0 ? 'The customer owes this amount.' : (walletBalance > 0 ? 'Advance held for the customer.' : 'Nothing outstanding.')"></p>

                    <template x-if="otherWalletBalances.length">
                        <div class="mt-3 border-t border-gray-200 pt-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Other currencies</p>
                            <template x-for="other in otherWalletBalances" :key="other.code">
                                <p class="mt-0.5 text-xs tabular-nums text-gray-600">
                                    <span x-text="other.code"></span>
                                    <span class="font-medium" x-text="money(other.balance)"></span>
                                </p>
                            </template>
                        </div>
                    </template>
                </div>

                <div>
                    <x-form.input name="received_amount" type="number" step="0.01" min="0"
                                  label="Collection / received now" x-model="received" placeholder="0.00" />
                    <p class="mt-1.5 text-xs text-gray-500">
                        Amount collected from the customer with this invoice, in
                        <span class="font-medium" x-text="currencyCode || 'the invoice currency'"></span>.
                        Leave it at 0 if nothing was collected.
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Balance after this invoice</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums"
                       :class="walletAfter < 0 ? 'text-rose-600' : 'text-emerald-600'"
                       x-text="money(walletAfter) + ' ' + currencyCode"></p>
                    <dl class="mt-3 space-y-1 border-t border-gray-100 pt-2 text-xs text-gray-500">
                        <div class="flex justify-between gap-3">
                            <dt>Opening</dt>
                            <dd class="tabular-nums" x-text="money(walletBalance)"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>Received now</dt>
                            <dd class="tabular-nums text-emerald-600" x-text="'+ ' + money(parseFloat(received) || 0)"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>This invoice</dt>
                            <dd class="tabular-nums text-rose-600" x-text="'− ' + money(total)"></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Invoice Items">
            <x-slot:actions>
                <x-button variant="secondary" icon="plus" @click="addItem()">Add Item</x-button>
            </x-slot:actions>

            <div class="space-y-4">
                <template x-for="(item, index) in items" :key="index">
                    <div class="relative rounded-lg border border-gray-200 p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-700">Item <span x-text="index + 1"></span></p>
                            <button type="button" @click="removeItem(index)"
                                    x-show="items.length > 1"
                                    class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                    title="Remove item">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>

                        <input type="hidden" :name="`items[${index}][id]`" x-model="item.id">

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                            <div class="xl:col-span-2">
                                <label :for="`item-${index}-description`" class="mb-1.5 block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                                <input type="text" :id="`item-${index}-description`" :name="`items[${index}][description]`"
                                       x-model="item.description" required placeholder="e.g. Papermaking towel felt"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-quantity`" class="mb-1.5 block text-sm font-medium text-gray-700">Qty / Weight</label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-quantity`" :name="`items[${index}][quantity]`"
                                       x-model="item.quantity" @input="recalc(item)" placeholder="e.g. 167.5"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-rate`" class="mb-1.5 block text-sm font-medium text-gray-700">Rate</label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-rate`" :name="`items[${index}][rate]`"
                                       x-model="item.rate" @input="recalc(item)" placeholder="e.g. 760"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-amount`" class="mb-1.5 block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-amount`" :name="`items[${index}][amount]`"
                                       x-model="item.amount" required placeholder="0.00"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
                <x-button variant="secondary" icon="plus" @click="addItem()">Add Item</x-button>
                <p class="text-sm text-gray-600">
                    Total:
                    <span class="text-lg font-semibold text-gray-900" x-text="total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span>
                </p>
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Payment Details">
            <p class="mb-4 text-sm text-gray-500">
                Prefilled from the site settings and printed under the invoice. Edit these to bill this invoice
                to a different account — clear them all to fall back to the site defaults.
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="bank_name" label="Bank"
                              :value="$paymentDetails['bank_name']" placeholder="e.g. The City Bank Limited" />
                <x-form.input name="bank_account_name" label="Account name"
                              :value="$paymentDetails['bank_account_name']" placeholder="e.g. Amatullah Trade International" />
                <x-form.input name="bank_account_number" label="Account number"
                              :value="$paymentDetails['bank_account_number']" placeholder="e.g. 1504311841001" />
                <x-form.input name="bank_branch" label="Branch"
                              :value="$paymentDetails['bank_branch']" placeholder="e.g. Sonargaon Janapath Branch, Uttara, Dhaka 1230" />
                <x-form.input name="bank_swift_code" label="SWIFT / BIC code"
                              :value="$paymentDetails['bank_swift_code']" placeholder="For incoming TT from abroad" />
                <x-form.input name="bank_routing_number" label="Routing number"
                              :value="$paymentDetails['bank_routing_number']" placeholder="For local BEFTN transfers" />
            </div>
        </x-card>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="route('admin.invoices.index')">Cancel</x-button>
        <x-button type="submit">{{ $invoice ? 'Save Changes' : 'Create Invoice' }}</x-button>
    </div>
</form>
