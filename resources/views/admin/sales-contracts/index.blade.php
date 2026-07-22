<x-admin-layout title="Sales Contracts">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Sales Contracts</h1>
            <p class="mt-1 text-sm text-gray-500">Export sales contracts with H.S. codes, units, freight, and terms.</p>
        </div>
        @can('sales-contracts.create')
            <x-button icon="plus" :href="route('admin.sales-contracts.create')">New Sales Contract</x-button>
        @endcan
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.sales-contracts.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search contract no or buyer…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($salesContracts->isEmpty())
            <x-empty-state icon="document" title="No sales contracts found"
                           description="Click 'New Sales Contract' to create your first contract." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Contract No</th>
                            <th class="px-4 py-3">Buyer</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($salesContracts as $salesContract)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.sales-contracts.show', $salesContract) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $salesContract->contract_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $salesContract->buyer }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $salesContract->contract_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $salesContract->items_count }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $salesContract->currency->symbol }}{{ number_format((float) ($salesContract->items_sum_amount ?? 0) + (float) $salesContract->freight_charge, 2) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $salesContract->currency->code }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.sales-contracts.show', $salesContract) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.sales-contracts.print', $salesContract) }}" target="_blank" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Print">
                                            <x-icon name="printer" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.sales-contracts.pdf', $salesContract) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Download PDF">
                                            <x-icon name="arrow-down-tray" class="size-4" />
                                        </a>
                                        @can('sales-contracts.edit')
                                            <a href="{{ route('admin.sales-contracts.edit', $salesContract) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('sales-contracts.delete')
                                            <form method="POST" action="{{ route('admin.sales-contracts.destroy', $salesContract) }}"
                                                  onsubmit="return confirm('Delete this sales contract and all its items?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete">
                                                    <x-icon name="trash" class="size-4" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-4 py-3 sm:px-6">
                {{ $salesContracts->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
