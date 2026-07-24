<x-admin-layout :title="'Invoice '.$invoice->invoice_no">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $invoice->invoice_no }}</h1>
                <x-badge color="indigo">{{ $invoice->currency->code }}</x-badge>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Billed to <span class="font-medium text-gray-700">{{ $invoice->bill_to }}</span>
                · {{ $invoice->invoice_date->format('d M Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button variant="secondary" :href="route('admin.invoices.print', $invoice)" target="_blank" icon="printer">Print</x-button>
            <x-button variant="secondary" :href="route('admin.invoices.pdf', $invoice)" icon="arrow-down-tray">PDF</x-button>
            <x-button variant="secondary" :href="route('admin.invoices.excel', $invoice)" icon="document-arrow-down">Excel</x-button>
            @can('invoices.edit')
                <x-button variant="secondary" :href="route('admin.invoices.edit', $invoice)" icon="pencil">Edit</x-button>
            @endcan
            @can('invoices.delete')
                <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}"
                      onsubmit="return confirm('Delete this invoice and all its items?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Line Items" :value="$invoice->items->count()" icon="receipt" color="indigo" />
        <x-stat-card label="Total Amount" :value="$invoice->currency->symbol.number_format((float) $totalAmount, 2)" icon="currency" color="emerald" />
        <x-stat-card label="Currency" :value="$invoice->currency->code" icon="currency" color="sky" />
    </div>

    <div class="mt-6">
        <x-card title="Invoice Items" :flush="true">
            @if ($invoice->items->isEmpty())
                <x-empty-state icon="receipt" title="No items" description="Edit the invoice to add line items." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">#</th>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3 text-right">Qty / Weight</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right sm:px-6">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($invoice->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $item->quantity !== null ? number_format((float) $item->quantity, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ $invoice->currency->symbol }}{{ number_format((float) $item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">Total</td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-gray-900 sm:px-6">
                                    {{ $invoice->currency->symbol }}{{ number_format((float) $totalAmount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
</x-admin-layout>
