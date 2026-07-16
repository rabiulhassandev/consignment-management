@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $code = $lcBill->currency->code;
@endphp

<x-pdf-layout heading="LC Bill">
    {{-- Billed to + bill meta --}}
    <table style="margin-top: 18px;">
        <tr>
            <td style="vertical-align: top;">
                <p class="muted uppercase" style="font-size: 10px; font-weight: bold;">Billed To</p>
                <p class="strong" style="font-size: 17px; margin-top: 3px;">{{ $lcBill->customer->name }}</p>
                @if ($lcBill->shipment_title)
                    <p class="muted" style="margin-top: 2px;">{{ $lcBill->shipment_title }}</p>
                @endif
            </td>
            <td class="right" style="vertical-align: top; width: 260px;">
                <table>
                    <tr>
                        <td class="muted">Bill No</td>
                        <td class="right strong num">{{ $lcBill->bill_no }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Bill Date</td>
                        <td class="right strong">{{ $lcBill->bill_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted">LC Number</td>
                        <td class="right strong num">{{ $lcBill->lc_number }}</td>
                    </tr>
                    @if ($lcBill->lc_value !== null)
                        <tr>
                            <td class="muted">LC Value</td>
                            <td class="right strong num">{{ number_format((float) $lcBill->lc_value, 2) }} {{ $code }}</td>
                        </tr>
                    @endif
                    @if ($lcBill->ci_value !== null)
                        <tr>
                            <td class="muted">CI Value</td>
                            <td class="right strong num">{{ number_format((float) $lcBill->ci_value, 2) }} {{ $code }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Received / Paid ledger --}}
    <table style="margin-top: 26px;">
        <tr>
            <td style="vertical-align: top; width: 50%; padding-right: 14px;">
                <p class="muted uppercase" style="font-size: 10px; font-weight: bold; border-bottom: 2px solid #1e293b; padding-bottom: 6px;">
                    Received ({{ $code }})
                </p>
                <table>
                    @forelse ($receipts as $entry)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 6px 6px 6px 0; vertical-align: top;">
                                <span style="color: #0f172a;">{{ $entry->description }}</span>
                                @if ($entry->entry_date || $entry->source_amount !== null)
                                    <br><span class="muted" style="font-size: 9px;">
                                        @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                        @if ($entry->source_amount !== null)
                                            @if ($entry->entry_date) &middot; @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="right strong num" style="padding: 6px 0; vertical-align: top;">{{ number_format((float) $entry->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="muted" style="padding: 6px 0;">No entries</td></tr>
                    @endforelse
                    <tr style="border-top: 2px solid #1e293b;">
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 6px;">Total Received</td>
                        <td class="right strong num" style="padding-top: 6px;">{{ number_format($totalReceived, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align: top; width: 50%; padding-left: 14px;">
                <p class="muted uppercase" style="font-size: 10px; font-weight: bold; border-bottom: 2px solid #1e293b; padding-bottom: 6px;">
                    Paid / Expenses ({{ $code }})
                </p>
                <table>
                    @forelse ($payments as $entry)
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 6px 6px 6px 0; vertical-align: top;">
                                <span style="color: #0f172a;">{{ $entry->description }}</span>
                                @if ($entry->entry_date || $entry->source_amount !== null)
                                    <br><span class="muted" style="font-size: 9px;">
                                        @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                        @if ($entry->source_amount !== null)
                                            @if ($entry->entry_date) &middot; @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                        @endif
                                    </span>
                                @endif
                            </td>
                            <td class="right strong num" style="padding: 6px 0; vertical-align: top;">{{ number_format((float) $entry->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="muted" style="padding: 6px 0;">No entries</td></tr>
                    @endforelse
                    <tr style="border-top: 2px solid #1e293b;">
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 6px;">Total Paid</td>
                        <td class="right strong num" style="padding-top: 6px;">{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Settlement summary --}}
    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 55%;"></td>
            <td>
                <table>
                    <tr>
                        <td class="muted">Total Received</td>
                        <td class="right num">{{ number_format($totalReceived, 2) }} {{ $code }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Total Paid</td>
                        <td class="right num">{{ number_format($totalPaid, 2) }} {{ $code }}</td>
                    </tr>
                    <tr style="border-top: 2px solid #1e293b;">
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 8px;">Balance</td>
                        <td class="right strong num" style="font-size: 15px; padding-top: 8px;">{{ number_format($balance, 2) }} {{ $code }}</td>
                    </tr>
                    @if ($lcBill->conversion_rate !== null)
                        <tr>
                            <td class="muted" style="padding-top: 4px;">Bank Rate</td>
                            <td class="right num" style="padding-top: 4px;">{{ (float) $lcBill->conversion_rate }}</td>
                        </tr>
                        <tr style="border-top: 1px solid #cbd5e1;">
                            <td class="strong uppercase" style="font-size: 10px; padding-top: 8px;">{{ $lcBill->is_settled ? 'Settled' : 'Due' }}</td>
                            <td class="right strong num" style="font-size: 20px; padding-top: 8px;"><span class="cjk">৳</span>{{ number_format($localDue, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Signature --}}
    <table style="margin-top: 50px; border-top: 1px solid #e5e7eb;">
        <tr>
            <td></td>
            <td class="right" style="padding-top: 60px; width: 220px;">
                <div style="border-top: 2px solid #1e293b; padding-top: 6px;">
                    <p style="color: #1e293b;">Authorized Signature</p>
                    <p class="muted" style="font-size: 10px; margin-top: 2px;">For {{ $companyName }}</p>
                </div>
            </td>
        </tr>
    </table>
</x-pdf-layout>
