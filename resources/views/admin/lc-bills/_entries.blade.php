{{-- Repeating entry rows for one side of the LC bill ledger ($side = 'receipts' | 'payments'). --}}
<x-card :title="$title">
    <x-slot:actions>
        <x-button variant="secondary" icon="plus" @click="add('{{ $side }}')">Add Entry</x-button>
    </x-slot:actions>

    <p class="mb-4 text-xs text-gray-500">{{ $hint }}</p>

    <div class="space-y-4">
        <template x-for="(entry, index) in {{ $side }}" :key="index">
            <div class="relative rounded-lg border border-gray-200 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-sm font-semibold text-gray-700">{{ $title }} <span x-text="index + 1"></span></p>
                    <button type="button" @click="remove('{{ $side }}', index)"
                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                            title="Remove entry">
                        <x-icon name="trash" class="size-4" />
                    </button>
                </div>

                <input type="hidden" :name="`{{ $side }}[${index}][id]`" x-model="entry.id">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label :for="`{{ $side }}-${index}-date`" class="mb-1.5 block text-sm font-medium text-gray-700">Date</label>
                        <input type="date" :id="`{{ $side }}-${index}-date`" :name="`{{ $side }}[${index}][entry_date]`"
                               x-model="entry.entry_date"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                    <div>
                        <label :for="`{{ $side }}-${index}-description`" class="mb-1.5 block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                        <input type="text" :id="`{{ $side }}-${index}-description`" :name="`{{ $side }}[${index}][description]`"
                               x-model="entry.description" required placeholder="e.g. Container freight"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                    <div>
                        <label :for="`{{ $side }}-${index}-source-amount`" class="mb-1.5 block text-sm font-medium text-gray-700">Source amount</label>
                        <input type="number" step="0.01" min="0" :id="`{{ $side }}-${index}-source-amount`" :name="`{{ $side }}[${index}][source_amount]`"
                               x-model="entry.source_amount" @input="convert(entry)" placeholder="e.g. 1200 (RMB)"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                    <div>
                        <label :for="`{{ $side }}-${index}-source-rate`" class="mb-1.5 block text-sm font-medium text-gray-700">Source rate</label>
                        <input type="number" step="0.0001" min="0" :id="`{{ $side }}-${index}-source-rate`" :name="`{{ $side }}[${index}][source_rate]`"
                               x-model="entry.source_rate" @input="convert(entry)" placeholder="e.g. 6.7"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                    <div class="sm:col-span-2">
                        <label :for="`{{ $side }}-${index}-amount`" class="mb-1.5 block text-sm font-medium text-gray-700">Amount (bill currency) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" :id="`{{ $side }}-${index}-amount`" :name="`{{ $side }}[${index}][amount]`"
                               x-model="entry.amount" required placeholder="0.00"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
        <x-button variant="secondary" icon="plus" @click="add('{{ $side }}')">Add Entry</x-button>
        <p class="text-sm text-gray-600">
            Subtotal:
            <span class="text-base font-semibold text-gray-900" x-text="money(sum('{{ $side }}'))"></span>
        </p>
    </div>
</x-card>
