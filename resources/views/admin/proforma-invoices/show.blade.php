@php
    $symbol = $proformaInvoice->currency->symbol;

    $shippingFields = [
        'Pre-Carriage' => $proformaInvoice->pre_carriage,
        'Place of Receipt' => $proformaInvoice->place_of_receipt,
        'Country of Origin' => $proformaInvoice->country_of_origin,
        'Port of Loading' => $proformaInvoice->port_of_loading,
        'Port of Discharge' => $proformaInvoice->port_of_discharge,
        'Final Destination' => $proformaInvoice->final_destination,
    ];
@endphp

<x-admin-layout :title="'Proforma Invoice '.$proformaInvoice->invoice_no">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">{{ $proformaInvoice->invoice_no }}</h1>
                <x-badge color="indigo">{{ $proformaInvoice->currency->code }}</x-badge>
                @if ($proformaInvoice->incoterm)
                    <x-badge color="sky">{{ $proformaInvoice->incoterm }}</x-badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Buyer <span class="font-medium text-gray-700">{{ $proformaInvoice->buyer_name }}</span>
                · {{ $proformaInvoice->invoice_date->format('d M Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button variant="secondary" :href="route('admin.proforma-invoices.print', $proformaInvoice)" target="_blank" icon="printer">Print</x-button>
            @can('proforma-invoices.edit')
                <x-button variant="secondary" :href="route('admin.proforma-invoices.edit', $proformaInvoice)" icon="pencil">Edit</x-button>
            @endcan
            @can('proforma-invoices.delete')
                <form method="POST" action="{{ route('admin.proforma-invoices.destroy', $proformaInvoice) }}"
                      onsubmit="return confirm('Delete this proforma invoice and all its items?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash">Delete</x-button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Line Items" :value="$proformaInvoice->items->count()" icon="truck" color="indigo" />
        <x-stat-card label="Total Amount" :value="$symbol.number_format($totalAmount, 2)" icon="currency" color="emerald" />
        <x-stat-card label="Currency" :value="$proformaInvoice->currency->code" icon="currency" color="sky" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Exporter">
            <p class="text-sm font-medium text-gray-900">{{ $proformaInvoice->exporter_name ?: '—' }}</p>
            @if ($proformaInvoice->exporter_address)
                <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $proformaInvoice->exporter_address }}</p>
            @endif
        </x-card>

        <x-card title="Importer / Buyer">
            <p class="text-sm font-medium text-gray-900">{{ $proformaInvoice->buyer_name }}</p>
            @if ($proformaInvoice->buyer_address)
                <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $proformaInvoice->buyer_address }}</p>
            @endif
        </x-card>
    </div>

    @if ($proformaInvoice->hasShippingDetails() || $proformaInvoice->delivery_payment_terms)
        <div class="mt-6">
            <x-card title="Shipping &amp; Delivery">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($shippingFields as $label => $value)
                        @if ($value)
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm text-gray-800">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                    @if ($proformaInvoice->delivery_payment_terms)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Terms of Delivery &amp; Payment</dt>
                            <dd class="mt-0.5 text-sm text-gray-800">{{ $proformaInvoice->delivery_payment_terms }}</dd>
                        </div>
                    @endif
                    @if ($proformaInvoice->mark)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Mark</dt>
                            <dd class="mt-0.5 text-sm text-gray-800">{{ $proformaInvoice->mark }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>
    @endif

    @if ($proformaInvoice->hasAdvisingBankDetails())
        <div class="mt-6">
            <x-card title="Exporter's LC Advising Bank">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($proformaInvoice->advising_bank_name)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Bank</dt>
                            <dd class="mt-0.5 text-sm text-gray-800">{{ $proformaInvoice->advising_bank_name }}</dd>
                        </div>
                    @endif
                    @if ($proformaInvoice->advising_bank_swift)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">SWIFT Code</dt>
                            <dd class="mt-0.5 text-sm tabular-nums text-gray-800">{{ $proformaInvoice->advising_bank_swift }}</dd>
                        </div>
                    @endif
                    @if ($proformaInvoice->advising_bank_address)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Bank Address</dt>
                            <dd class="mt-0.5 text-sm text-gray-800">{{ $proformaInvoice->advising_bank_address }}</dd>
                        </div>
                    @endif
                    @if ($proformaInvoice->beneficiary_name)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Beneficiary Name</dt>
                            <dd class="mt-0.5 text-sm text-gray-800">{{ $proformaInvoice->beneficiary_name }}</dd>
                        </div>
                    @endif
                    @if ($proformaInvoice->beneficiary_account)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-400">Beneficiary A/C</dt>
                            <dd class="mt-0.5 text-sm tabular-nums text-gray-800">{{ $proformaInvoice->beneficiary_account }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>
    @endif

    <div class="mt-6">
        <x-card title="Description of Goods" :flush="true">
            @if ($proformaInvoice->items->isEmpty())
                <x-empty-state icon="truck" title="No items" description="Edit the invoice to add line items." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-4 py-3 sm:px-6">#</th>
                                <th class="px-4 py-3">Description of Goods</th>
                                <th class="px-4 py-3">H.S. Code No.</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3 text-right">Rate</th>
                                <th class="px-4 py-3 text-right sm:px-6">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($proformaInvoice->items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-400 sm:px-6">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item->description }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->hs_code ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $item->quantityLabel() ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-600">
                                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900 sm:px-6">
                                        {{ $symbol }}{{ number_format((float) $item->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200">
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-700 sm:px-6">
                                    Total{{ $proformaInvoice->incoterm ? ' ('.$proformaInvoice->incoterm.')' : '' }}
                                </td>
                                <td class="px-4 py-3 text-right text-base font-semibold text-gray-900 sm:px-6">
                                    {{ $symbol }}{{ number_format($totalAmount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 sm:px-6">
                    <span class="font-medium text-gray-700">Say:</span> {{ $amountInWords }}
                </div>
            @endif
        </x-card>
    </div>

    @if ($proformaInvoice->declaration)
        <div class="mt-6">
            <x-card title="Declaration">
                <p class="text-sm text-gray-600">{{ $proformaInvoice->declaration }}</p>
            </x-card>
        </div>
    @endif
</x-admin-layout>
