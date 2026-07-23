@php
    /** @var \App\Models\ProformaInvoice|null $proformaInvoice */
    $proformaInvoice = $proformaInvoice ?? null;
    $defaults = $defaults ?? [];

    /** Existing value on edit, otherwise the company default prefilled on create. */
    $field = fn (string $key): string => (string) ($proformaInvoice?->{$key} ?? $defaults[$key] ?? '');

    $emptyItem = [
        'id' => null,
        'description' => '',
        'hs_code' => '',
        'quantity' => '',
        'unit' => '',
        'rate' => '',
        'amount' => '',
    ];

    $initialItems = old('items') ?? $proformaInvoice?->items->map(fn ($item) => [
        'id' => $item->id,
        'description' => $item->description,
        'hs_code' => $item->hs_code ?? '',
        'quantity' => $item->quantity !== null ? (string) $item->quantity : '',
        'unit' => $item->unit ?? '',
        'rate' => $item->rate !== null ? (string) $item->rate : '',
        'amount' => (string) $item->amount,
    ])->values()->all() ?? [$emptyItem];

    $itemErrors = collect($errors->get('items.*'))->flatten()->unique();
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          items: {{ Js::from($initialItems) }},
          emptyItem: {{ Js::from($emptyItem) }},
          addItem() { this.items.push({ ...this.emptyItem }) },
          removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1) } },
          recalc(item) {
              const quantity = parseFloat(item.quantity);
              const rate = parseFloat(item.rate);
              if (!isNaN(quantity) && !isNaN(rate)) { item.amount = (quantity * rate).toFixed(2) }
          },
          get total() { return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0) },
      }">
    @csrf
    @if ($proformaInvoice)
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
            <x-form.input name="invoice_no" label="Invoice no"
                          :value="$proformaInvoice?->invoice_no ?? $suggestedNumber ?? ''" required />
            <x-form.input name="invoice_date" type="date" label="Date"
                          :value="$proformaInvoice?->invoice_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.select name="currency_id" label="Currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $proformaInvoice?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
            <x-form.input name="incoterm" label="Incoterm" :value="$field('incoterm')" placeholder="e.g. CFR" />
        </div>
    </x-card>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Exporter">
            <p class="mb-4 text-sm text-gray-500">Prefilled from your company settings — edit it when a different entity exports.</p>
            <div class="space-y-4">
                <x-form.input name="exporter_name" label="Exporter name" :value="$field('exporter_name')" />
                <x-form.textarea name="exporter_address" label="Exporter address" rows="3" :value="$field('exporter_address')" />
            </div>
        </x-card>

        <x-card title="Importer / Buyer">
            <div class="space-y-4">
                <x-form.input name="buyer_name" label="Buyer name"
                              :value="$proformaInvoice?->buyer_name ?? ''" placeholder="Importer / company name" required />
                <x-form.textarea name="buyer_address" label="Buyer address" rows="3"
                                 :value="$proformaInvoice?->buyer_address ?? ''" />
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Exporter's LC Advising Bank">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-form.input name="advising_bank_name" label="Bank name" :value="$field('advising_bank_name')" />
                <x-form.input name="advising_bank_swift" label="SWIFT code" :value="$field('advising_bank_swift')" />
                <x-form.textarea name="advising_bank_address" label="Bank address" rows="2"
                                 :value="$field('advising_bank_address')" class="sm:col-span-2" />
                <x-form.input name="beneficiary_name" label="Beneficiary name" :value="$field('beneficiary_name')" />
                <x-form.input name="beneficiary_account" label="Beneficiary A/C" :value="$field('beneficiary_account')" />
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Shipping &amp; Delivery">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-form.input name="pre_carriage" label="Pre-carriage"
                              :value="$proformaInvoice?->pre_carriage ?? ''" placeholder="e.g. By Sea / By Air" />
                <x-form.input name="place_of_receipt" label="Place of receipt"
                              :value="$proformaInvoice?->place_of_receipt ?? ''" placeholder="e.g. HongKong / China" />
                <x-form.input name="country_of_origin" label="Country of origin"
                              :value="$proformaInvoice?->country_of_origin ?? ''" placeholder="e.g. China" />
                <x-form.input name="port_of_loading" label="Port of loading"
                              :value="$proformaInvoice?->port_of_loading ?? ''" placeholder="e.g. Any port of China/HongKong" />
                <x-form.input name="port_of_discharge" label="Port of discharge"
                              :value="$proformaInvoice?->port_of_discharge ?? ''" placeholder="e.g. Chattogram, Bangladesh" />
                <x-form.input name="final_destination" label="Final destination"
                              :value="$proformaInvoice?->final_destination ?? ''" placeholder="e.g. ICD Kamalapur, Dhaka" />
                <x-form.input name="delivery_payment_terms" label="Terms of delivery and payment"
                              :value="$proformaInvoice?->delivery_payment_terms ?? ''" placeholder="e.g. BY LCAF / TT"
                              class="sm:col-span-2" />
                <x-form.input name="mark" label="Mark"
                              :value="$proformaInvoice?->mark ?? ''" placeholder="e.g. N/M" />
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Description of Goods">
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

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                            <div class="xl:col-span-2">
                                <label :for="`item-${index}-description`" class="mb-1.5 block text-sm font-medium text-gray-700">Description of goods <span class="text-red-500">*</span></label>
                                <input type="text" :id="`item-${index}-description`" :name="`items[${index}][description]`"
                                       x-model="item.description" required placeholder="e.g. Polyester fabric"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-hs-code`" class="mb-1.5 block text-sm font-medium text-gray-700">H.S. Code No.</label>
                                <input type="text" :id="`item-${index}-hs-code`" :name="`items[${index}][hs_code]`"
                                       x-model="item.hs_code" placeholder="e.g. 5407.61.00"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label :for="`item-${index}-quantity`" class="mb-1.5 block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" step="0.01" min="0" :id="`item-${index}-quantity`" :name="`items[${index}][quantity]`"
                                           x-model="item.quantity" @input="recalc(item)" placeholder="e.g. 500"
                                           class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                                <div>
                                    <label :for="`item-${index}-unit`" class="mb-1.5 block text-sm font-medium text-gray-700">Unit</label>
                                    <input type="text" :id="`item-${index}-unit`" :name="`items[${index}][unit]`"
                                           x-model="item.unit" placeholder="e.g. YDS"
                                           class="block w-full rounded-lg border-0 px-3 py-2 text-sm uppercase text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:normal-case placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                            </div>
                            <div>
                                <label :for="`item-${index}-rate`" class="mb-1.5 block text-sm font-medium text-gray-700">Rate</label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-rate`" :name="`items[${index}][rate]`"
                                       x-model="item.rate" @input="recalc(item)" placeholder="e.g. 1.85"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-amount`" class="mb-1.5 block text-sm font-medium text-gray-700">Total amount <span class="text-red-500">*</span></label>
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
        <x-card title="Declaration">
            <x-form.textarea name="declaration" label="Declaration" rows="3" :value="$field('declaration')"
                             placeholder="Printed above the authorised signature on the invoice." />
        </x-card>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="route('admin.proforma-invoices.index')">Cancel</x-button>
        <x-button type="submit">{{ $proformaInvoice ? 'Save Changes' : 'Create Proforma Invoice' }}</x-button>
    </div>
</form>
