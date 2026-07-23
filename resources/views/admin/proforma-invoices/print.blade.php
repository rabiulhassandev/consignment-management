@php
    $currency = $proformaInvoice->currency;
    $symbol = $currency->symbol;
    $amountHeading = 'Total Amount ('.$currency->code.')';
    $blankRows = max(0, 6 - $proformaInvoice->items->count());
    $rowSpan = $proformaInvoice->items->count() + $blankRows;

    $shippingCells = [
        ['Pre-Carriage', $proformaInvoice->pre_carriage],
        ['Place of Receipt', $proformaInvoice->place_of_receipt],
        ['Country of Origin', $proformaInvoice->country_of_origin],
        ['Port of Loading', $proformaInvoice->port_of_loading],
        ['Port of Discharge', $proformaInvoice->port_of_discharge],
        ['Final Destination', $proformaInvoice->final_destination],
    ];
@endphp

<x-print-page :title="'Proforma Invoice '.$proformaInvoice->invoice_no"
              :back-url="route('admin.proforma-invoices.show', $proformaInvoice)"
              heading="Proforma">
    <p class="mt-7 text-center text-2xl font-bold uppercase tracking-[0.15em] text-slate-900">Proforma Invoice</p>

    {{-- Parties, invoice meta, and advising bank --}}
    <table class="mt-4 w-full text-[12px]">
        <tr>
            <td class="w-1/2 border border-slate-800 p-0 align-top">
                <div class="px-3 py-2">
                    <p class="text-[11px] font-bold text-slate-800 underline">Exporter</p>
                    <p class="mt-0.5 text-[15px] font-bold text-slate-900">{{ $proformaInvoice->exporter_name }}</p>
                    @if ($proformaInvoice->exporter_address)
                        <p class="mt-0.5 whitespace-pre-line leading-snug text-slate-700">{{ $proformaInvoice->exporter_address }}</p>
                    @endif
                </div>
                <div class="border-t border-slate-800 px-3 py-2">
                    <p class="text-[11px] font-bold text-slate-800 underline">Importer/Buyer:</p>
                    <p class="mt-0.5 text-[15px] font-bold text-slate-900">{{ $proformaInvoice->buyer_name }}</p>
                    @if ($proformaInvoice->buyer_address)
                        <p class="mt-0.5 whitespace-pre-line leading-snug text-slate-700">{{ $proformaInvoice->buyer_address }}</p>
                    @endif
                </div>
            </td>
            <td class="w-1/2 border border-slate-800 p-0 align-top">
                <div class="px-3 py-2">
                    <span class="font-bold text-slate-800">INVOICE NO.:</span>
                    <span class="font-bold text-slate-900">{{ $proformaInvoice->invoice_no }}</span>
                </div>
                <div class="border-t border-slate-800 px-3 py-2">
                    <span class="font-bold text-slate-800">DATE:</span>
                    <span class="font-bold text-slate-900">{{ $proformaInvoice->invoice_date->format('Y/m/d') }}</span>
                </div>
                @if ($proformaInvoice->hasAdvisingBankDetails())
                    <div class="border-t border-slate-800 px-3 py-2 leading-snug">
                        <p class="font-bold text-slate-800">EXPORTER'S LC ADVISING BANK :</p>
                        @if ($proformaInvoice->advising_bank_name)
                            <p class="text-slate-800">{{ $proformaInvoice->advising_bank_name }}</p>
                        @endif
                        @if ($proformaInvoice->advising_bank_address)
                            <p class="text-slate-700">ADD: {{ $proformaInvoice->advising_bank_address }}</p>
                        @endif
                        @if ($proformaInvoice->advising_bank_swift)
                            <p class="text-slate-800">SWIFT CODE: <span class="tabular-nums">{{ $proformaInvoice->advising_bank_swift }}</span></p>
                        @endif
                        @if ($proformaInvoice->beneficiary_name)
                            <p class="text-slate-800">BENEFICIARY NAME: {{ $proformaInvoice->beneficiary_name }}</p>
                        @endif
                        @if ($proformaInvoice->beneficiary_account)
                            <p class="text-slate-800">BENEFICIARY A/C: <span class="tabular-nums">{{ $proformaInvoice->beneficiary_account }}</span></p>
                        @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Shipping routing + delivery terms --}}
    <table class="w-full text-[12px]">
        @foreach (array_chunk($shippingCells, 2) as $rowIndex => $pair)
            <tr>
                @foreach ($pair as [$label, $value])
                    <td class="w-1/4 border border-slate-800 px-3 py-1.5 align-top">
                        <p class="text-[11px] font-bold text-slate-800">{{ $label }}</p>
                        <p class="text-slate-700">{{ $value ?: '—' }}</p>
                    </td>
                @endforeach
                @if ($rowIndex === 0)
                    <td rowspan="3" class="w-1/2 border border-slate-800 px-3 py-2 text-center align-middle">
                        <p class="text-[12px] font-bold text-slate-800">Terms of Delivery and Payment:</p>
                        <p class="mt-1 font-bold uppercase text-slate-900">{{ $proformaInvoice->delivery_payment_terms ?: '—' }}</p>
                    </td>
                @endif
            </tr>
        @endforeach
    </table>

    {{-- Description of goods --}}
    <table class="w-full text-[12px]">
        <thead>
            <tr class="text-center text-[11px] font-bold leading-tight text-slate-800">
                <th class="w-16 border border-slate-800 px-2 py-2">Mark</th>
                <th class="border border-slate-800 px-2 py-2">Description of Goods</th>
                <th class="w-20 border border-slate-800 px-2 py-2">H.S. Code No.</th>
                <th class="w-20 border border-slate-800 px-2 py-2">Quantity</th>
                <th class="w-20 border border-slate-800 px-2 py-2">Rate<br>({{ $currency->code }})</th>
                <th class="w-28 border border-slate-800 px-2 py-2">
                    {{ $amountHeading }}
                    @if ($proformaInvoice->incoterm)
                        <br>{{ $proformaInvoice->incoterm }}
                    @endif
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proformaInvoice->items as $item)
                <tr>
                    @if ($loop->first)
                        <td rowspan="{{ $rowSpan }}" class="border border-slate-800 px-2 py-2 text-center align-middle font-medium text-slate-800">
                            {{ $proformaInvoice->mark }}
                        </td>
                    @endif
                    <td class="border border-slate-800 px-3 py-2 text-slate-900">{{ $item->description }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-center tabular-nums text-slate-700">{{ $item->hs_code }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-center tabular-nums text-slate-700">{{ $item->quantityLabel() }}</td>
                    <td class="border border-slate-800 px-2 py-2 text-right tabular-nums text-slate-700">
                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '' }}
                    </td>
                    <td class="border border-slate-800 px-2 py-2 text-right font-medium tabular-nums text-slate-900">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- Ruled blank rows so short invoices keep the printed grid --}}
            @for ($i = 0; $i < $blankRows; $i++)
                <tr>
                    @if ($proformaInvoice->items->isEmpty() && $i === 0)
                        <td rowspan="{{ $rowSpan }}" class="border border-slate-800 px-2 py-2 text-center align-middle font-medium text-slate-800">
                            {{ $proformaInvoice->mark }}
                        </td>
                    @endif
                    @for ($column = 0; $column < 5; $column++)
                        <td class="border border-slate-800 px-2 py-2">&nbsp;</td>
                    @endfor
                </tr>
            @endfor

            <tr>
                <td colspan="5" class="border border-slate-800 px-3 py-2 font-bold uppercase text-slate-800">
                    Total : Say {{ $proformaInvoice->incoterm ? '('.$proformaInvoice->incoterm.') ' : '' }}{{ $currency->name }}
                </td>
                <td class="border border-slate-800 px-2 py-2 text-right text-[14px] font-bold tabular-nums text-slate-900">
                    {{ $symbol }}{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <p class="mt-2 text-[12px] font-medium text-slate-800">{{ $amountInWords }}</p>

    {{-- Declaration + authorised signature --}}
    <div class="mt-14 flex items-end justify-between gap-10">
        <div class="text-[12px] leading-relaxed">
            @if ($proformaInvoice->declaration)
                <p class="font-bold text-slate-800">Declaration</p>
                <p class="mt-0.5 max-w-sm text-slate-600">{{ $proformaInvoice->declaration }}</p>
            @endif
        </div>
        <div class="shrink-0 text-center">
            <p class="mb-14 text-[12px] italic text-gray-500">For and on behalf of {{ $proformaInvoice->exporter_name }}</p>
            <div class="w-64 border-t-2 border-dotted border-slate-800"></div>
            <p class="mt-1.5 text-[12px] font-bold uppercase text-slate-800">Authorised Signature</p>
        </div>
    </div>
</x-print-page>
