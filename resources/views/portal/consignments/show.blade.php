<x-portal-layout :title="'Consignment '.$consignment->consignment_no">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $consignment->consignment_no }}</h1>
                <x-badge color="indigo">{{ $consignment->currency->code }}</x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ $consignment->consignment_date->format('d M Y') }}</p>
        </div>
        <x-button variant="secondary" :href="route('portal.consignments.index')">Back to list</x-button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat-card label="Purchase Items" :value="$consignment->items->count()" icon="document" color="indigo" />
        <x-stat-card label="Total Amount" :value="$consignment->currency->symbol.number_format((float) $totalAmount, 2)" icon="currency" color="emerald" />
    </div>

    <div class="mt-6">
        <x-card title="Purchase Items" :flush="true">
            @if ($consignment->items->isEmpty())
                <x-empty-state icon="document" title="No purchase items yet" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">#</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Supplier</th>
                                <th class="px-4 py-3">Sample No</th>
                                <th class="px-4 py-3">Own Sample No</th>
                                <th class="px-4 py-3 text-right sm:px-6">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($consignment->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->purchase_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3"><x-badge>{{ $item->category->name }}</x-badge></td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->supplier->name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->sample_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->own_sample_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ $consignment->currency->symbol }}{{ number_format((float) $item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="7" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">Total</td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-gray-900 sm:px-6">
                                    {{ $consignment->currency->symbol }}{{ number_format((float) $totalAmount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-portal-layout>
