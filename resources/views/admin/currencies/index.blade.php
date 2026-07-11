<x-admin-layout title="Currencies">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Currencies</h1>
            <p class="mt-1 text-sm text-gray-500">Currencies available when creating consignments and bills.</p>
        </div>
        <x-button icon="plus" x-data @click="$dispatch('open-modal', 'add-currency')">New Currency</x-button>
    </div>

    <x-card :flush="true" class="max-w-4xl">
        @if ($currencies->isEmpty())
            <x-empty-state icon="currency" title="No currencies yet" description="Add currencies like USD, CNY or BDT." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Name</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Symbol</th>
                            <th class="px-4 py-3">Consignments</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($currencies as $currency)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $currency->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $currency->code }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $currency->symbol }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $currency->consignments_count }}</td>
                                <td class="px-4 py-3">
                                    <x-badge :color="$currency->is_active ? 'green' : 'gray'">
                                        {{ $currency->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" x-data @click="$dispatch('open-modal', 'edit-currency-{{ $currency->id }}')"
                                                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                            <x-icon name="pencil" class="size-4" />
                                        </button>
                                        <form method="POST" action="{{ route('admin.currencies.destroy', $currency) }}"
                                              onsubmit="return confirm('Delete this currency?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                <x-icon name="trash" class="size-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($currencies->hasPages())
                <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                    {{ $currencies->links() }}
                </div>
            @endif
        @endif
    </x-card>

    <x-modal name="add-currency" title="New Currency" max-width="md">
        <form method="POST" action="{{ route('admin.currencies.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_modal" value="add-currency">
            <x-form.input name="name" id="add-currency-name" label="Currency name" placeholder="US Dollar" required />
            <div class="grid grid-cols-2 gap-4">
                <x-form.input name="code" id="add-currency-code" label="Code" placeholder="USD" required />
                <x-form.input name="symbol" id="add-currency-symbol" label="Symbol" placeholder="$" required />
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked
                       class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                Active
            </label>
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-button variant="secondary" @click="show = false">Cancel</x-button>
                <x-button type="submit">Create</x-button>
            </div>
        </form>
    </x-modal>

    @foreach ($currencies as $currency)
        <x-modal name="edit-currency-{{ $currency->id }}" title="Edit Currency" max-width="md">
            <form method="POST" action="{{ route('admin.currencies.update', $currency) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="edit-currency-{{ $currency->id }}">
                <x-form.input name="name" id="edit-currency-{{ $currency->id }}-name" label="Currency name" :value="$currency->name" required />
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input name="code" id="edit-currency-{{ $currency->id }}-code" label="Code" :value="$currency->code" required />
                    <x-form.input name="symbol" id="edit-currency-{{ $currency->id }}-symbol" label="Symbol" :value="$currency->symbol" required />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked($currency->is_active)
                           class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    Active
                </label>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <x-button variant="secondary" @click="show = false">Cancel</x-button>
                    <x-button type="submit">Save</x-button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-admin-layout>
