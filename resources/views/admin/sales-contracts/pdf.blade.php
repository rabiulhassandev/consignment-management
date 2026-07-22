@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $currency = $salesContract->currency;
    $symbol = $currency->symbol;
    $blankRows = max(0, 4 - $salesContract->items->count());

    // Shared cell borders — dompdf collapses these reliably on a table with border-collapse.
    $cell = 'border: 1px solid #1e293b; padding: 6px 8px;';
    $head = $cell.' background-color: #f8fafc; font-size: 10px; font-weight: bold; text-transform: uppercase; text-align: center;';
@endphp

<x-pdf-layout heading="Contract">
    {{-- Buyer + contract meta --}}
    <table style="margin-top: 22px; border-collapse: collapse; font-size: 11px;">
        <tr>
            <td style="{{ $cell }} width: 62px; font-weight: bold; vertical-align: top;">Buyer</td>
            <td style="{{ $cell }} vertical-align: top;"><span class="cjk">{{ $salesContract->buyer }}</span></td>
            <td style="{{ $cell }} width: 76px; font-weight: bold; vertical-align: top;">Date</td>
            <td style="{{ $cell }} width: 118px; font-weight: bold; text-transform: uppercase; vertical-align: top;">
                {{ $salesContract->contract_date->format('d F Y') }}
            </td>
        </tr>
        <tr>
            <td style="{{ $cell }} font-weight: bold; vertical-align: top;">Address</td>
            <td style="{{ $cell }} vertical-align: top;"><span class="cjk">{{ $salesContract->buyer_address ?: '—' }}</span></td>
            <td style="{{ $cell }} font-weight: bold; vertical-align: top;">Contract No.</td>
            <td style="{{ $cell }} font-weight: bold; vertical-align: top;">{{ $salesContract->contract_no }}</td>
        </tr>
    </table>

    {{-- Items --}}
    <table style="margin-top: 18px; border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr>
                <th style="{{ $head }} width: 34px;">Sl. No.</th>
                <th style="{{ $head }}">Description</th>
                <th style="{{ $head }} width: 68px;">H.S. Code</th>
                <th style="{{ $head }} width: 56px;">Quantity</th>
                <th style="{{ $head }} width: 50px;">Unit</th>
                <th style="{{ $head }} width: 62px;">Unit / {{ $currency->code }}</th>
                <th style="{{ $head }} width: 78px;">Total / {{ $currency->code }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesContract->items as $item)
                <tr>
                    <td style="{{ $cell }} text-align: center; color: #64748b;">{{ $loop->iteration }}</td>
                    <td style="{{ $cell }} color: #0f172a;"><span class="cjk">{{ $item->description }}</span></td>
                    <td class="num" style="{{ $cell }} text-align: center;">{{ $item->hs_code }}</td>
                    <td class="num" style="{{ $cell }} text-align: center;">
                        {{ $item->quantity !== null ? rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') : '' }}
                    </td>
                    <td style="{{ $cell }} text-align: center; text-transform: uppercase;">{{ $item->unit }}</td>
                    <td class="num" style="{{ $cell }} text-align: right;">
                        {{ $item->unit_price !== null ? number_format((float) $item->unit_price, 2) : '' }}
                    </td>
                    <td class="num strong" style="{{ $cell }} text-align: right;">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach

            {{-- Ruled blank rows so short contracts keep the printed grid --}}
            @for ($i = 0; $i < $blankRows; $i++)
                <tr>
                    @for ($column = 0; $column < 7; $column++)
                        <td style="{{ $cell }}">&nbsp;</td>
                    @endfor
                </tr>
            @endfor

            @if ($salesContract->freight_charge !== null)
                <tr>
                    <td style="{{ $cell }}">&nbsp;</td>
                    <td style="{{ $cell }} text-transform: uppercase; color: #0f172a;">Freight Charge</td>
                    <td style="{{ $cell }}">&nbsp;</td>
                    <td style="{{ $cell }}">&nbsp;</td>
                    <td style="{{ $cell }}">&nbsp;</td>
                    <td class="num" style="{{ $cell }} text-align: right;">
                        {{ number_format((float) $salesContract->freight_charge, 2) }}
                    </td>
                    <td class="num strong" style="{{ $cell }} text-align: right;">
                        {{ number_format((float) $salesContract->freight_charge, 2) }}
                    </td>
                </tr>
            @endif

            <tr>
                <td style="{{ $cell }} background-color: #f8fafc;">&nbsp;</td>
                <td colspan="5" style="{{ $cell }} background-color: #f8fafc; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #0f172a;">
                    Total Amount
                </td>
                <td class="num" style="{{ $cell }} background-color: #f8fafc; text-align: right; font-size: 13px; font-weight: bold; color: #0f172a;">
                    <span class="cjk">{{ $symbol }}</span>{{ number_format($totalAmount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Amount in words --}}
    <table style="margin-top: 14px;">
        <tr>
            <td class="strong" style="width: 62px; vertical-align: top; font-size: 11px;">In Words</td>
            <td class="strong" style="vertical-align: top; font-size: 11px; font-weight: normal;">{{ $amountInWords }}</td>
        </tr>
    </table>

    {{-- Terms & conditions --}}
    @if ($termLines->isNotEmpty())
        <table style="margin-top: 18px;">
            <tr>
                <td>
                    <p class="strong uppercase" style="font-size: 11px;">Terms and Condition:</p>
                    <table style="margin-top: 6px;">
                        @foreach ($termLines as $line)
                            <tr>
                                <td style="width: 26px; padding: 2px 0 2px 14px; vertical-align: top; font-size: 11px; color: #475569;">
                                    {{ $loop->iteration }}
                                </td>
                                <td style="padding: 2px 0; vertical-align: top; font-size: 11px; color: #475569;">
                                    <span class="cjk">{{ $line }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Confirmation blocks --}}
    <table style="margin-top: 30px; border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr>
                <th style="{{ $cell }} width: 50%; text-align: center; font-size: 13px; font-weight: bold; color: #0f172a;">Seller Confirmation</th>
                <th style="{{ $cell }} width: 50%; text-align: center; font-size: 13px; font-weight: bold; color: #0f172a;">Buyer Confirmation</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="{{ $cell }} height: 76px; vertical-align: bottom; text-align: center; font-size: 10px; color: #94a3b8;">
                    For {{ $companyName }}
                </td>
                <td style="{{ $cell }} height: 76px; vertical-align: bottom; text-align: center; font-size: 10px; color: #94a3b8;">
                    <span class="cjk">For {{ $salesContract->buyer }}</span>
                </td>
            </tr>
        </tbody>
    </table>

    @if ($footerNote)
        <p class="muted" style="text-align: center; font-style: italic; font-size: 11px; margin-top: 22px;">
            {{ $footerNote }}
        </p>
    @endif
</x-pdf-layout>
