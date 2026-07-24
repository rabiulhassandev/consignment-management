@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $bankName = \App\Models\Setting::get('bank_name');
    $bankAccountName = \App\Models\Setting::get('bank_account_name');
    $bankAccountNumber = \App\Models\Setting::get('bank_account_number');
    $bankBranch = \App\Models\Setting::get('bank_branch');
    $bankSwiftCode = \App\Models\Setting::get('bank_swift_code');
    $bankRoutingNumber = \App\Models\Setting::get('bank_routing_number');
    $paymentTerms = \App\Models\Setting::get('invoice_payment_terms');
    $terms = \App\Models\Setting::get('invoice_terms');
    $signatoryName = \App\Models\Setting::get('invoice_signatory_name');
    $signatoryDesignation = \App\Models\Setting::get('invoice_signatory_designation');
    $footerNote = \App\Models\Setting::get('invoice_footer_note');
    $hasBankDetails = $bankName || $bankAccountName || $bankAccountNumber || $bankBranch || $bankSwiftCode || $bankRoutingNumber;
    $symbol = $invoice->currency->symbol;
@endphp

<x-pdf-layout heading="Invoice">
    {{-- Billed to + invoice meta --}}
    <table style="margin-top: 26px;">
        <tr>
            <td style="vertical-align: top;">
                <table>
                    <tr>
                        <td style="vertical-align: top; padding-top: 4px; padding-right: 14px; width: 74px;">
                            <span class="muted uppercase" style="font-size: 10px; font-weight: bold;">Billed To</span>
                        </td>
                        <td style="vertical-align: top;">
                            <span class="strong" style="font-size: 19px;">{{ $invoice->bill_to }}</span>
                            @if ($invoice->bill_to_address)
                                <p class="muted" style="font-size: 11px; line-height: 1.6; margin-top: 4px;">{{ $invoice->bill_to_address }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td class="right" style="vertical-align: top; width: 250px;">
                <table>
                    <tr>
                        <td class="muted">Invoice No</td>
                        <td class="right strong num">{{ $invoice->invoice_no }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Issue Date</td>
                        <td class="right strong">{{ $invoice->invoice_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Currency</td>
                        <td class="right strong">{{ $invoice->currency->code }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <table style="margin-top: 34px;">
        <thead>
            <tr class="muted uppercase" style="font-size: 10px; border-bottom: 2px solid #1e293b;">
                <th style="text-align: left; padding-bottom: 8px;">Description</th>
                <th class="right" style="padding-bottom: 8px;">Qty / Weight</th>
                <th class="right" style="padding-bottom: 8px;">Rate</th>
                <th class="right" style="padding-bottom: 8px;">Amount ({{ $invoice->currency->code }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 11px 8px 11px 0; color: #0f172a;">{{ $item->description }}</td>
                    <td class="right num muted" style="padding: 11px 0;">
                        {{ $item->quantity !== null ? number_format((float) $item->quantity, 2) : '—' }}
                    </td>
                    <td class="right num muted" style="padding: 11px 0;">
                        {{ $item->rate !== null ? number_format((float) $item->rate, 2) : '—' }}
                    </td>
                    <td class="right num strong" style="padding: 11px 0;">
                        {{ number_format((float) $item->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Total --}}
    <table style="margin-top: 22px;">
        <tr>
            <td style="width: 52%;"></td>
            <td>
                <table style="border-top: 2px solid #1e293b;">
                    <tr>
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 12px; vertical-align: bottom;">Total</td>
                        <td class="right strong num" style="font-size: 26px; padding-top: 12px;">
                            <span class="cjk">{{ $symbol }}</span>{{ number_format((float) $totalAmount, 2) }}
                        </td>
                    </tr>
                </table>
                <p class="right muted" style="font-size: 10px; margin-top: 6px;">
                    Amount in {{ $invoice->currency->name }} ({{ $invoice->currency->code }}) only
                </p>
                @if ($paymentTerms)
                    <p class="right strong" style="font-size: 10px; margin-top: 4px;">{{ $paymentTerms }}</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- Terms & conditions --}}
    @if ($terms)
        <table style="margin-top: 30px;">
            <tr>
                <td>
                    <p class="muted uppercase" style="font-size: 10px; font-weight: bold;">Terms &amp; Conditions</p>
                    <p class="dark" style="font-size: 11px; line-height: 1.6; margin-top: 4px;">{{ $terms }}</p>
                </td>
            </tr>
        </table>
    @endif

    {{-- Payment details + signature --}}
    <table style="margin-top: 44px; border-top: 1px solid #e5e7eb;">
        <tr>
            <td style="vertical-align: bottom; padding-top: 20px; color: #64748b; font-size: 11px; line-height: 1.8;">
                @if ($hasBankDetails)
                    <p class="muted uppercase" style="font-size: 10px; font-weight: bold;">Payment Details</p>
                    @if ($bankName)
                        <p><span style="display: inline-block; width: 110px;">Bank</span><span class="dark">{{ $bankName }}</span></p>
                    @endif
                    @if ($bankAccountName)
                        <p><span style="display: inline-block; width: 110px;">Account Name</span><span class="dark">{{ $bankAccountName }}</span></p>
                    @endif
                    @if ($bankAccountNumber)
                        <p><span style="display: inline-block; width: 110px;">Account No.</span><span class="dark">{{ $bankAccountNumber }}</span></p>
                    @endif
                    @if ($bankBranch)
                        <p><span style="display: inline-block; width: 110px;">Branch</span><span class="dark">{{ $bankBranch }}</span></p>
                    @endif
                    @if ($bankSwiftCode)
                        <p><span style="display: inline-block; width: 110px;">SWIFT / BIC</span><span class="dark num">{{ $bankSwiftCode }}</span></p>
                    @endif
                    @if ($bankRoutingNumber)
                        <p><span style="display: inline-block; width: 110px;">Routing No.</span><span class="dark num">{{ $bankRoutingNumber }}</span></p>
                    @endif
                @endif
            </td>
            <td class="center" style="vertical-align: bottom; padding-top: 56px; width: 250px;">
                <div style="border-top: 2px solid #1e293b; padding-top: 8px;">
                    @if ($signatoryName)
                        <p class="dark strong" style="font-size: 13px;">{{ $signatoryName }}</p>
                        @if ($signatoryDesignation)
                            <p class="muted" style="font-size: 10px; margin-top: 2px;">{{ $signatoryDesignation }}</p>
                        @endif
                    @else
                        <p class="dark" style="font-size: 13px;">Authorized Signature</p>
                    @endif
                    <p class="muted" style="font-size: 10px; margin-top: 3px;">For {{ $companyName }}</p>
                </div>
            </td>
        </tr>
    </table>

    @if ($footerNote)
        <p class="muted" style="text-align: center; font-style: italic; font-size: 11px; margin-top: 34px;">
            {{ $footerNote }}
        </p>
    @endif
</x-pdf-layout>
