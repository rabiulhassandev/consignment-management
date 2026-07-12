<x-admin-layout title="Search Results">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Search Results</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if ($query !== '')
                Results for "<span class="font-medium text-gray-900">{{ $query }}</span>" across sample numbers, own sample numbers and consignment numbers.
            @else
                Type a sample number, own sample number or consignment number in the search box above.
            @endif
        </p>
    </div>

    @if ($query !== '')
        <div class="space-y-6">
            <x-card title="Matching Purchase Items ({{ $items->count() }})" :flush="true">
                @if ($items->isEmpty())
                    <x-empty-state icon="search" title="No purchase items matched"
                                   description="No sample number or own sample number contains '{{ $query }}'." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50/75">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 sm:px-6">Sample No</th>
                                    <th class="px-4 py-3">Own Sample No</th>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Supplier</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Consignment</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-right sm:px-6">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $item->sample_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->own_sample_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->product_name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->supplier->name }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.customers.show', $item->consignment->customer) }}" class="text-indigo-600 hover:text-indigo-700">
                                                {{ $item->consignment->customer->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.consignments.show', $item->consignment) }}" class="text-indigo-600 hover:text-indigo-700">
                                                {{ $item->consignment->consignment_no }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item->purchase_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                            {{ $item->consignment->currency->symbol }}{{ number_format((float) $item->amount, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card title="Matching Consignments ({{ $consignments->count() }})" :flush="true">
                @if ($consignments->isEmpty())
                    <x-empty-state icon="search" title="No consignments matched"
                                   description="No consignment number contains '{{ $query }}'." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50/75">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3 sm:px-6">Consignment No</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Items</th>
                                    <th class="px-4 py-3 text-right sm:px-6">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($consignments as $consignment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 sm:px-6">
                                            <a href="{{ route('admin.consignments.show', $consignment) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                                {{ $consignment->consignment_no }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.customers.show', $consignment->customer) }}" class="text-indigo-600 hover:text-indigo-700">
                                                {{ $consignment->customer->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $consignment->consignment_date->format('d M Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $consignment->items_count }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                            {{ $consignment->currency->symbol }}{{ number_format((float) ($consignment->items_sum_amount ?? 0), 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>
    @endif
</x-admin-layout>
