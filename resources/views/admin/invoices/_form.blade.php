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
            <x-form.input name="invoice_no" label="Invoice number"
                          :value="$invoice?->invoice_no ?? $suggestedNumber ?? ''" required />
            <x-form.input name="bill_to" label="Bill to"
                          :value="$invoice?->bill_to ?? ''" placeholder="Customer / company name" required />
            <x-form.input name="invoice_date" type="date" label="Invoice date"
                          :value="$invoice?->invoice_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.select name="currency_id" label="Currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $invoice?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
        </div>
    </x-card>

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

    <div class="mt-6 flex items-center justify-end gap-3">
        <x-button variant="secondary" :href="route('admin.invoices.index')">Cancel</x-button>
        <x-button type="submit">{{ $invoice ? 'Save Changes' : 'Create Invoice' }}</x-button>
    </div>
</form>
