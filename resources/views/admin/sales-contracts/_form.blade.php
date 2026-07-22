@php
    /** @var \App\Models\SalesContract|null $salesContract */
    $salesContract = $salesContract ?? null;

    $emptyItem = [
        'id' => null,
        'description' => '',
        'hs_code' => '',
        'quantity' => '',
        'unit' => '',
        'unit_price' => '',
        'amount' => '',
    ];

    $initialItems = old('items') ?? $salesContract?->items->map(fn ($item) => [
        'id' => $item->id,
        'description' => $item->description,
        'hs_code' => $item->hs_code ?? '',
        'quantity' => $item->quantity !== null ? (string) $item->quantity : '',
        'unit' => $item->unit ?? '',
        'unit_price' => $item->unit_price !== null ? (string) $item->unit_price : '',
        'amount' => (string) $item->amount,
    ])->values()->all() ?? [$emptyItem];

    $initialFreight = old('freight_charge', $salesContract?->freight_charge !== null ? (string) $salesContract->freight_charge : '');

    $itemErrors = collect($errors->get('items.*'))->flatten()->unique();
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
          items: {{ Js::from($initialItems) }},
          emptyItem: {{ Js::from($emptyItem) }},
          freight: {{ Js::from($initialFreight) }},
          addItem() { this.items.push({ ...this.emptyItem }) },
          removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1) } },
          recalc(item) {
              const quantity = parseFloat(item.quantity);
              const unitPrice = parseFloat(item.unit_price);
              if (!isNaN(quantity) && !isNaN(unitPrice)) { item.amount = (quantity * unitPrice).toFixed(2) }
          },
          get itemsTotal() { return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0) },
          get total() { return this.itemsTotal + (parseFloat(this.freight) || 0) },
          money(value) { return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
      }">
    @csrf
    @if ($salesContract)
        @method('PUT')
    @endif

    @if ($itemErrors->isNotEmpty() || $errors->has('items'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please fix the contract items below:</p>
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

    <x-card title="Contract Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-form.input name="contract_no" label="Contract / invoice no"
                          :value="$salesContract?->contract_no ?? $suggestedNumber ?? ''" required />
            <x-form.input name="buyer" label="Buyer"
                          :value="$salesContract?->buyer ?? ''" placeholder="Buyer / company name" required />
            <x-form.input name="contract_date" type="date" label="Date"
                          :value="$salesContract?->contract_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.select name="currency_id" label="Currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $salesContract?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
        </div>

        <div class="mt-4">
            <x-form.input name="buyer_address" label="Buyer address"
                          :value="$salesContract?->buyer_address ?? ''" placeholder="Street, city, country" />
        </div>
    </x-card>

    <div class="mt-6">
        <x-card title="Contract Items">
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
                                <label :for="`item-${index}-description`" class="mb-1.5 block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                                <input type="text" :id="`item-${index}-description`" :name="`items[${index}][description]`"
                                       x-model="item.description" required placeholder="e.g. Bag Accessories"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-hs-code`" class="mb-1.5 block text-sm font-medium text-gray-700">H.S. Code</label>
                                <input type="text" :id="`item-${index}-hs-code`" :name="`items[${index}][hs_code]`"
                                       x-model="item.hs_code" placeholder="e.g. 4202.92.00"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label :for="`item-${index}-quantity`" class="mb-1.5 block text-sm font-medium text-gray-700">Quantity</label>
                                    <input type="number" step="0.01" min="0" :id="`item-${index}-quantity`" :name="`items[${index}][quantity]`"
                                           x-model="item.quantity" @input="recalc(item)" placeholder="e.g. 600"
                                           class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                                <div>
                                    <label :for="`item-${index}-unit`" class="mb-1.5 block text-sm font-medium text-gray-700">Unit</label>
                                    <input type="text" :id="`item-${index}-unit`" :name="`items[${index}][unit]`"
                                           x-model="item.unit" placeholder="e.g. SETS"
                                           class="block w-full rounded-lg border-0 px-3 py-2 text-sm uppercase text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:normal-case placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                </div>
                            </div>
                            <div>
                                <label :for="`item-${index}-unit-price`" class="mb-1.5 block text-sm font-medium text-gray-700">Unit price</label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-unit-price`" :name="`items[${index}][unit_price]`"
                                       x-model="item.unit_price" @input="recalc(item)" placeholder="e.g. 42.00"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-amount`" class="mb-1.5 block text-sm font-medium text-gray-700">Total <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" min="0" :id="`item-${index}-amount`" :name="`items[${index}][amount]`"
                                       x-model="item.amount" required placeholder="0.00"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex flex-wrap items-end justify-between gap-4 border-t border-gray-100 pt-4">
                <x-button variant="secondary" icon="plus" @click="addItem()">Add Item</x-button>

                <div class="flex flex-wrap items-end gap-6">
                    <div class="w-44">
                        <label for="freight_charge" class="mb-1.5 block text-sm font-medium text-gray-700">Freight charge</label>
                        <input type="number" step="0.01" min="0" id="freight_charge" name="freight_charge"
                               x-model="freight" placeholder="0.00"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        @error('freight_charge')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pb-2 text-right text-sm text-gray-600">
                        <p>Items subtotal: <span class="font-medium text-gray-900" x-text="money(itemsTotal)"></span></p>
                        <p class="mt-1">
                            Total amount:
                            <span class="text-lg font-semibold text-gray-900" x-text="money(total)"></span>
                        </p>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Terms &amp; Conditions">
            <x-form.textarea name="terms" label="Terms and conditions" rows="8"
                             :value="$salesContract?->terms ?? $defaultTerms ?? ''"
                             placeholder="One condition per line — printed as a numbered list on the contract. Include account details here if required." />
        </x-card>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="route('admin.sales-contracts.index')">Cancel</x-button>
        <x-button type="submit">{{ $salesContract ? 'Save Changes' : 'Create Sales Contract' }}</x-button>
    </div>
</form>
