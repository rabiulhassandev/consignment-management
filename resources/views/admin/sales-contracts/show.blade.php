@php
    $symbol = $salesContract->currency->symbol;
@endphp

<x-admin-layout :title="'Sales Contract '.$salesContract->contract_no">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $salesContract->contract_no }}</h1>
                <x-badge color="indigo">{{ $salesContract->currency->code }}</x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Buyer <span class="font-medium text-gray-700">{{ $salesContract->buyer }}</span>
                · {{ $salesContract->contract_date->format('d M Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button variant="secondary" :href="route('admin.sales-contracts.print', $salesContract)" target="_blank" icon="printer">Print</x-button>
            @can('sales-contracts.edit')
                <x-button variant="secondary" :href="route('admin.sales-contracts.edit', $salesContract)" icon="pencil">Edit</x-button>
            @endcan
            @can('sales-contracts.delete')
                <form method="POST" action="{{ route('admin.sales-contracts.destroy', $salesContract) }}"
                      onsubmit="return confirm('Delete this sales contract and all its items?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Line Items" :value="$salesContract->items->count()" icon="document" color="indigo" />
        <x-stat-card label="Items Subtotal" :value="$symbol.number_format($itemsTotal, 2)" icon="receipt" color="sky" />
        <x-stat-card label="Freight Charge" :value="$symbol.number_format((float) $salesContract->freight_charge, 2)" icon="truck" color="amber" />
        <x-stat-card label="Total Amount" :value="$symbol.number_format($totalAmount, 2)" icon="currency" color="emerald" />
    </div>

    @if ($salesContract->buyer_address)
        <div class="mt-6">
            <x-card title="Buyer">
                <p class="text-sm font-medium text-gray-900">{{ $salesContract->buyer }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $salesContract->buyer_address }}</p>
            </x-card>
        </div>
    @endif

    <div class="mt-6">
        <x-card title="Contract Items" :flush="true">
            @if ($salesContract->items->isEmpty())
                <x-empty-state icon="document" title="No items" description="Edit the contract to add line items." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">Sl.</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">H.S. Code</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3 text-right">Unit Price</th>
                                <th class="px-4 py-3 text-right sm:px-6">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($salesContract->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->hs_code ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $item->quantity !== null ? number_format((float) $item->quantity, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 uppercase text-gray-600">{{ $item->unit ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ $symbol }}{{ number_format((float) $item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="text-gray-700">
                            @if ($salesContract->freight_charge !== null)
                                <tr class="border-t border-gray-200">
                                    <td colspan="6" class="px-4 py-3 text-right text-sm font-medium sm:px-6">Freight Charge</td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 sm:px-6">
                                        {{ $symbol }}{{ number_format((float) $salesContract->freight_charge, 2) }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold sm:px-6">Total Amount</td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-gray-900 sm:px-6">
                                    {{ $symbol }}{{ number_format($totalAmount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 sm:px-6">
                    <span class="font-medium text-gray-700">In words:</span> {{ $amountInWords }}
                </div>
            @endif
        </x-card>
    </div>

    @if ($termLines->isNotEmpty())
        <div class="mt-6">
            <x-card title="Terms &amp; Conditions">
                <ol class="list-inside list-decimal space-y-1 text-sm text-gray-600">
                    @foreach ($termLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ol>
            </x-card>
        </div>
    @endif
</x-admin-layout>
