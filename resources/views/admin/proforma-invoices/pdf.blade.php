@php
    $currency = $proformaInvoice->currency;
    $symbol = $currency->symbol;
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

    // Shared cell borders — dompdf collapses these reliably on a table with border-collapse.
    $cell = 'border: 1px solid #1e293b; padding: 5px 8px;';
    $head = $cell.' background-color: #f8fafc; font-size: 10px; font-weight: bold; text-align: center;';
@endphp

<x-pdf-layout heading="Proforma">
    <p style="text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; color: #0f172a; margin-top: 16px;">
        Proforma Invoice
    </p>

    {{-- Parties, invoice meta, and advising bank --}}
    <table style="margin-top: 12px; border-collapse: collapse; font-size: 10px;">
        <tr>
            <td style="{{ $cell }} width: 50%; padding: 0; vertical-align: top;">
                <div style="padding: 6px 8px;">
                    <p style="font-size: 10px; font-weight: bold; color: #1e293b; text-decoration: underline;">Exporter</p>
                    <p class="strong cjk" style="font-size: 13px; padding-top: 2px;">{{ $proformaInvoice->exporter_name }}</p>
                    @if ($proformaInvoice->exporter_address)
                        <p class="cjk" style="color: #475569; padding-top: 2px;">{{ $proformaInvoice->exporter_address }}</p>
                    @endif
                </div>
                <div style="border-top: 1px solid #1e293b; padding: 6px 8px;">
                    <p style="font-size: 10px; font-weight: bold; color: #1e293b; text-decoration: underline;">Importer/Buyer:</p>
                    <p class="strong cjk" style="font-size: 13px; padding-top: 2px;">{{ $proformaInvoice->buyer_name }}</p>
                    @if ($proformaInvoice->buyer_address)
                        <p class="cjk" style="color: #475569; padding-top: 2px;">{{ $proformaInvoice->buyer_address }}</p>
                    @endif
                </div>
            </td>
            <td style="{{ $cell }} width: 50%; padding: 0; vertical-align: top;">
                <div style="padding: 6px 8px;">
                    <span class="strong">INVOICE NO.:</span> <span class="strong">{{ $proformaInvoice->invoice_no }}</span>
                </div>
                <div style="border-top: 1px solid #1e293b; padding: 6px 8px;">
                    <span class="strong">DATE:</span> <span class="strong">{{ $proformaInvoice->invoice_date->format('Y/m/d') }}</span>
                </div>
                @if ($proformaInvoice->hasAdvisingBankDetails())
                    <div style="border-top: 1px solid #1e293b; padding: 6px 8px; line-height: 1.45;">
                        <p class="strong">EXPORTER'S LC ADVISING BANK :</p>
                        @if ($proformaInvoice->advising_bank_name)
                            <p class="dark cjk">{{ $proformaInvoice->advising_bank_name }}</p>
                        @endif
                        @if ($proformaInvoice->advising_bank_address)
                            <p class="cjk" style="color: #475569;">ADD: {{ $proformaInvoice->advising_bank_address }}</p>
                        @endif
                        @if ($proformaInvoice->advising_bank_swift)
                            <p class="dark num">SWIFT CODE: {{ $proformaInvoice->advising_bank_swift }}</p>
                        @endif
                        @if ($proformaInvoice->beneficiary_name)
                            <p class="dark cjk">BENEFICIARY NAME: {{ $proformaInvoice->beneficiary_name }}</p>
                        @endif
                        @if ($proformaInvoice->beneficiary_account)
                            <p class="dark num">BENEFICIARY A/C: {{ $proformaInvoice->beneficiary_account }}</p>
                        @endif
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Shipping routing + delivery terms --}}
    <table style="border-collapse: collapse; font-size: 10px;">
        @foreach (array_chunk($shippingCells, 2) as $rowIndex => $pair)
            <tr>
                @foreach ($pair as [$label, $value])
                    <td style="{{ $cell }} width: 25%; vertical-align: top;">
                        <p style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ $label }}</p>
                        <p class="cjk" style="color: #475569;">{{ $value ?: '—' }}</p>
                    </td>
                @endforeach
                @if ($rowIndex === 0)
                    <td rowspan="3" style="{{ $cell }} width: 50%; text-align: center; vertical-align: middle;">
                        <p class="strong" style="font-size: 11px;">Terms of Delivery and Payment:</p>
                        <p class="strong" style="text-transform: uppercase; padding-top: 4px;">
                            {{ $proformaInvoice->delivery_payment_terms ?: '—' }}
                        </p>
                    </td>
                @endif
            </tr>
        @endforeach
    </table>

    {{-- Description of goods --}}
    <table style="border-collapse: collapse; font-size: 10px;">
        <thead>
            <tr>
                <th style="{{ $head }} width: 46px;">Mark</th>
                <th style="{{ $head }}">Description of Goods</th>
                <th style="{{ $head }} width: 62px;">H.S. Code No.</th>
                <th style="{{ $head }} width: 56px;">Quantity</th>
                <th style="{{ $head }} width: 54px;">Rate<br>({{ $currency->code }})</th>
                <th style="{{ $head }} width: 84px;">
                    Total Amount ({{ $currency->code }})
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
                        <td rowspan="{{ $rowSpan }}" style="{{ $cell }} text-align: center; vertical-align: middle; font-weight: bold; color: #1e293b;">
                            {{ $proformaInvoice->mark }}
                        </td>
                    @endif
                    <td style="{{ $cell }} color: #0f172a;"><span class="cjk">{{ $item->description }}</span></td>
                    <td class="num" style="{{ $cell }} text-align: center;">{{ $item->hs_code }}</td>
                    <td class="num" style="{{ $cell }} text-align: center;">{{ $item->quantityLabel() }}</td>
                    <td class="num" style="{{ $cell }} text-align: right;">
                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '' }}
                    </td>
                    <td class="num strong" style="{{ $cell }} text-align: right;">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- Ruled blank rows so short invoices keep the printed grid --}}
            @for ($i = 0; $i < $blankRows; $i++)
                <tr>
                    @if ($proformaInvoice->items->isEmpty() && $i === 0)
                        <td rowspan="{{ $rowSpan }}" style="{{ $cell }} text-align: center; vertical-align: middle; font-weight: bold; color: #1e293b;">
                            {{ $proformaInvoice->mark }}
                        </td>
                    @endif
                    @for ($column = 0; $column < 5; $column++)
                        <td style="{{ $cell }}">&nbsp;</td>
                    @endfor
                </tr>
            @endfor

            <tr>
                <td colspan="5" style="{{ $cell }} background-color: #f8fafc; font-weight: bold; text-transform: uppercase; color: #0f172a;">
                    Total : Say {{ $proformaInvoice->incoterm ? '('.$proformaInvoice->incoterm.') ' : '' }}{{ $currency->name }}
                </td>
                <td class="num" style="{{ $cell }} background-color: #f8fafc; text-align: right; font-size: 12px; font-weight: bold; color: #0f172a;">
                    <span class="cjk">{{ $symbol }}</span>{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <p class="dark" style="font-size: 10px; font-weight: bold; margin-top: 6px;">{{ $amountInWords }}</p>

    {{-- Declaration + authorised signature --}}
    <table style="margin-top: 46px;">
        <tr>
            <td style="vertical-align: bottom; font-size: 10px; line-height: 1.6;">
                @if ($proformaInvoice->declaration)
                    <p class="strong">Declaration</p>
                    <p style="color: #475569; padding-top: 2px;">{{ $proformaInvoice->declaration }}</p>
                @endif
            </td>
            <td class="right" style="vertical-align: bottom; width: 250px;">
                <p class="muted" style="font-size: 10px; font-style: italic; padding-bottom: 40px;">
                    For and on behalf of <span class="cjk">{{ $proformaInvoice->exporter_name }}</span>
                </p>
                <div style="border-top: 2px dotted #1e293b; padding-top: 5px;">
                    <p class="strong uppercase" style="font-size: 11px;">Authorised Signature</p>
                </div>
            </td>
        </tr>
    </table>
</x-pdf-layout>
