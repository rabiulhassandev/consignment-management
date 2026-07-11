@php
    /** @var \App\Models\Consignment|null $consignment */
    $consignment = $consignment ?? null;

    $emptyItem = [
        'id' => null,
        'purchase_date' => now()->format('Y-m-d'),
        'product_name' => '',
        'category_id' => '',
        'supplier_id' => '',
        'sample_number' => '',
        'own_sample_number' => '',
        'amount' => '',
    ];

    $initialItems = old('items') ?? $consignment?->items->map(fn ($item) => [
        'id' => $item->id,
        'purchase_date' => $item->purchase_date->format('Y-m-d'),
        'product_name' => $item->product_name,
        'category_id' => (string) $item->category_id,
        'supplier_id' => (string) $item->supplier_id,
        'sample_number' => $item->sample_number ?? '',
        'own_sample_number' => $item->own_sample_number ?? '',
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
          get total() { return this.items.reduce((sum, item) => sum + (parseFloat(item.amount) || 0), 0) },
      }">
    @csrf
    @if ($consignment)
        @method('PUT')
    @endif

    @if ($suppliers->isEmpty())
        <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            This customer has no suppliers yet. Purchase items require a supplier —
            <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold underline">add suppliers on the customer profile</a> first.
        </div>
    @endif

    @if ($itemErrors->isNotEmpty() || $errors->has('items'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="mb-1 font-semibold">Please fix the purchase items below:</p>
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

    <x-card title="Consignment Details">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-form.input name="consignment_no" label="Consignment number"
                          :value="$consignment?->consignment_no ?? $suggestedNumber ?? ''" required />
            <x-form.input name="consignment_date" type="date" label="Consignment date"
                          :value="$consignment?->consignment_date->format('Y-m-d') ?? now()->format('Y-m-d')" required />
            <x-form.select name="currency_id" label="Currency" placeholder="Select currency" required>
                @foreach ($currencies as $currency)
                    <option value="{{ $currency->id }}"
                            @selected((int) old('currency_id', $consignment?->currency_id) === $currency->id)>
                        {{ $currency->code }} — {{ $currency->name }} ({{ $currency->symbol }})
                    </option>
                @endforeach
            </x-form.select>
        </div>
    </x-card>

    <div class="mt-6">
        <x-card title="Purchase Items">
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

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label :for="`item-${index}-date`" class="mb-1.5 block text-sm font-medium text-gray-700">Date <span class="text-red-500">*</span></label>
                                <input type="date" :id="`item-${index}-date`" :name="`items[${index}][purchase_date]`"
                                       x-model="item.purchase_date" required
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-product`" class="mb-1.5 block text-sm font-medium text-gray-700">Product name <span class="text-red-500">*</span></label>
                                <input type="text" :id="`item-${index}-product`" :name="`items[${index}][product_name]`"
                                       x-model="item.product_name" required placeholder="e.g. Cotton twill fabric"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-category`" class="mb-1.5 block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                <select :id="`item-${index}-category`" :name="`items[${index}][category_id]`"
                                        x-model="item.category_id" required
                                        class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                    <option value="">Select category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label :for="`item-${index}-supplier`" class="mb-1.5 block text-sm font-medium text-gray-700">Supplier <span class="text-red-500">*</span></label>
                                <select :id="`item-${index}-supplier`" :name="`items[${index}][supplier_id]`"
                                        x-model="item.supplier_id" required
                                        class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                                    <option value="">Select supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label :for="`item-${index}-sample`" class="mb-1.5 block text-sm font-medium text-gray-700">Sample number</label>
                                <input type="text" :id="`item-${index}-sample`" :name="`items[${index}][sample_number]`"
                                       x-model="item.sample_number"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            </div>
                            <div>
                                <label :for="`item-${index}-own-sample`" class="mb-1.5 block text-sm font-medium text-gray-700">Own sample number</label>
                                <input type="text" :id="`item-${index}-own-sample`" :name="`items[${index}][own_sample_number]`"
                                       x-model="item.own_sample_number"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
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
        <x-button variant="secondary" :href="route('admin.customers.show', $customer)">Cancel</x-button>
        <x-button type="submit">{{ $consignment ? 'Save Changes' : 'Create Consignment' }}</x-button>
    </div>
</form>
