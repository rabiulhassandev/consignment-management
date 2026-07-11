<x-admin-layout title="Consignments">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Consignments</h1>
        <p class="mt-1 text-sm text-gray-500">All consignments across customers. Create new ones from a customer's profile.</p>
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.consignments.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search consignment no…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <select name="customer" onchange="this.form.submit()"
                    class="rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                <option value="">All customers</option>
                @foreach ($customers as $customerOption)
                    <option value="{{ $customerOption->id }}" @selected($customerId === $customerOption->id)>{{ $customerOption->name }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($consignments->isEmpty())
            <x-empty-state icon="cube" title="No consignments found"
                           description="Open a customer's profile and click 'New Consignment' to create one." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Consignment No</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($consignments as $consignment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.consignments.show', $consignment) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $consignment->consignment_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.customers.show', $consignment->customer) }}" class="text-gray-600 hover:text-indigo-600">
                                        {{ $consignment->customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $consignment->consignment_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $consignment->items_count }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $consignment->currency->symbol }}{{ number_format((float) ($consignment->items_sum_amount ?? 0), 2) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $consignment->currency->code }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.consignments.show', $consignment) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        @can('consignments.edit')
                                            <a href="{{ route('admin.consignments.edit', $consignment) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('consignments.delete')
                                            <form method="POST" action="{{ route('admin.consignments.destroy', $consignment) }}"
                                                  onsubmit="return confirm('Delete this consignment and all its purchase items?')">
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
                {{ $consignments->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
