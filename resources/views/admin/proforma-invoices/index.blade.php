<x-admin-layout title="Proforma Invoices">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Proforma Invoices</h1>
            <p class="mt-1 text-sm text-gray-500">Export proforma invoices with shipping routing and advising bank details.</p>
        </div>
        @can('proforma-invoices.create')
            <x-button icon="plus" :href="route('admin.proforma-invoices.create')">New Proforma Invoice</x-button>
        @endcan
    </div>

    <x-card :flush="true">
        <form method="GET" action="{{ route('admin.proforma-invoices.index') }}"
              class="flex flex-wrap items-center gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <div class="relative max-w-xs flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="search" value="{{ $search }}" placeholder="Search invoice no or buyer…"
                       class="block w-full rounded-lg border-0 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>

        @if ($proformaInvoices->isEmpty())
            <x-empty-state icon="truck" title="No proforma invoices found"
                           description="Click 'New Proforma Invoice' to create your first one." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">Invoice No</th>
                            <th class="px-4 py-3">Buyer</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Destination</th>
                            <th class="px-4 py-3">Items</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($proformaInvoices as $proformaInvoice)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 sm:px-6">
                                    <a href="{{ route('admin.proforma-invoices.show', $proformaInvoice) }}" class="font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $proformaInvoice->invoice_no }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $proformaInvoice->buyer_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $proformaInvoice->invoice_date->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $proformaInvoice->final_destination ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $proformaInvoice->items_count }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $proformaInvoice->currency->symbol }}{{ number_format((float) ($proformaInvoice->items_sum_amount ?? 0), 2) }}
                                    <span class="text-xs font-normal text-gray-400">{{ $proformaInvoice->currency->code }}</span>
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.proforma-invoices.show', $proformaInvoice) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="View">
                                            <x-icon name="eye" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.proforma-invoices.print', $proformaInvoice) }}" target="_blank" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Print">
                                            <x-icon name="printer" class="size-4" />
                                        </a>
                                        <a href="{{ route('admin.proforma-invoices.pdf', $proformaInvoice) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Download PDF">
                                            <x-icon name="arrow-down-tray" class="size-4" />
                                        </a>
                                        @can('proforma-invoices.edit')
                                            <a href="{{ route('admin.proforma-invoices.edit', $proformaInvoice) }}" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-indigo-600" title="Edit">
                                                <x-icon name="pencil" class="size-4" />
                                            </a>
                                        @endcan
                                        @can('proforma-invoices.delete')
                                            <form method="POST" action="{{ route('admin.proforma-invoices.destroy', $proformaInvoice) }}"
                                                  onsubmit="return confirm('Delete this proforma invoice and all its items?')">
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
                {{ $proformaInvoices->links() }}
            </div>
        @endif
    </x-card>
</x-admin-layout>
