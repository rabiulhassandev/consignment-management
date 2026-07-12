<x-admin-layout :title="'LC Bill '.$lcBill->bill_no">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $lcBill->bill_no }}</h1>
                <x-badge color="indigo">{{ $lcBill->currency->code }}</x-badge>
                <x-badge :color="$lcBill->is_settled ? 'green' : 'yellow'">
                    {{ $lcBill->is_settled ? 'Settled' : 'Unsettled' }}
                </x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                <a href="{{ route('admin.customers.show', $lcBill->customer) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $lcBill->customer->name }}</a>
                · LC {{ $lcBill->lc_number }}
                · {{ $lcBill->bill_date->format('d M Y') }}
                @if ($lcBill->shipment_title)
                    · {{ $lcBill->shipment_title }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button variant="secondary" :href="route('admin.lc-bills.print', $lcBill)" target="_blank" icon="printer">Print</x-button>
            @can('lc-bills.edit')
                <x-button variant="secondary" :href="route('admin.lc-bills.edit', $lcBill)" icon="pencil">Edit</x-button>
            @endcan
            @can('lc-bills.delete')
                <form method="POST" action="{{ route('admin.lc-bills.destroy', $lcBill) }}"
                      onsubmit="return confirm('Delete this LC bill and all its entries?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Received" :value="$lcBill->currency->symbol.number_format($totalReceived, 2)" icon="banknotes" color="emerald" />
        <x-stat-card label="Total Paid" :value="$lcBill->currency->symbol.number_format($totalPaid, 2)" icon="banknotes" color="rose" />
        <x-stat-card label="Balance" :value="$lcBill->currency->symbol.number_format($balance, 2)" icon="currency" color="indigo" />
        <x-stat-card label="Due (BDT)" :value="$localDue !== null ? '৳'.number_format($localDue, 2) : '—'" icon="currency" color="amber" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Received" :flush="true">
            @if ($receipts->isEmpty())
                <x-empty-state icon="banknotes" title="No received entries" description="Edit the bill to record received amounts." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">Date</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right sm:px-6">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($receipts as $entry)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 sm:px-6">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-900">
                                        {{ $entry->description }}
                                        @if ($entry->source_amount !== null)
                                            <span class="block text-xs text-gray-400">
                                                {{ number_format((float) $entry->source_amount, 2) }} @ {{ (float) $entry->source_rate }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">Total Received</td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-emerald-600 sm:px-6">
                                    {{ $lcBill->currency->symbol }}{{ number_format($totalReceived, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Paid / Expenses" :flush="true">
            @if ($payments->isEmpty())
                <x-empty-state icon="banknotes" title="No paid entries" description="Edit the bill to record expenses." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">Date</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right sm:px-6">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($payments as $entry)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 sm:px-6">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-900">
                                        {{ $entry->description }}
                                        @if ($entry->source_amount !== null)
                                            <span class="block text-xs text-gray-400">
                                                {{ number_format((float) $entry->source_amount, 2) }} @ {{ (float) $entry->source_rate }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">Total Paid</td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-rose-600 sm:px-6">
                                    {{ $lcBill->currency->symbol }}{{ number_format($totalPaid, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="LC Details">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">LC number</dt>
                    <dd class="font-medium text-gray-900">{{ $lcBill->lc_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">LC value</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $lcBill->lc_value !== null ? $lcBill->currency->symbol.number_format((float) $lcBill->lc_value, 2) : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">CI value</dt>
                    <dd class="font-medium text-gray-900">
                        {{ $lcBill->ci_value !== null ? $lcBill->currency->symbol.number_format((float) $lcBill->ci_value, 2) : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Shipment</dt>
                    <dd class="font-medium text-gray-900">{{ $lcBill->shipment_title ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Settlement">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Balance ({{ $lcBill->currency->code }})</dt>
                    <dd class="font-medium text-gray-900">{{ $lcBill->currency->symbol }}{{ number_format($balance, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Bank rate</dt>
                    <dd class="font-medium text-gray-900">{{ $lcBill->conversion_rate !== null ? (float) $lcBill->conversion_rate : '—' }}</dd>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-3">
                    <dt class="font-semibold text-gray-700">Due (BDT)</dt>
                    <dd class="text-lg font-semibold {{ $localDue !== null && $localDue > 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $localDue !== null ? '৳'.number_format($localDue, 2) : '—' }}
                    </dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-admin-layout>
