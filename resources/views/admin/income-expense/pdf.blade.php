@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $signatoryName = \App\Models\Setting::get('invoice_signatory_name');
    $signatoryDesignation = \App\Models\Setting::get('invoice_signatory_designation');
@endphp

<x-pdf-layout heading="Statement">
    {{-- Statement meta --}}
    <table style="margin-top: 18px;">
        <tr>
            <td style="vertical-align: top;">
                <p class="muted uppercase" style="font-size: 10px; font-weight: bold;">Statement Period</p>
                <p class="strong" style="font-size: 17px; margin-top: 3px;">{{ $periodLabel }}</p>
                <p class="muted" style="margin-top: 2px;">{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</p>
            </td>
            <td class="right" style="vertical-align: top; width: 240px;">
                <table>
                    <tr>
                        <td class="muted">Report Type</td>
                        <td class="right strong">{{ $periodName }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Statement Date</td>
                        <td class="right strong">{{ now()->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Entries</td>
                        <td class="right strong num">{{ $statement->count() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Statement lines --}}
    <table style="margin-top: 26px;">
        <thead>
            <tr class="muted uppercase" style="font-size: 9px; border-bottom: 2px solid #1e293b;">
                <th style="text-align: left; padding: 0 6px 6px 0; width: 70px;">{{ $period === 'yearly' ? 'Month' : 'Date' }}</th>
                <th style="text-align: left; padding: 0 6px 6px 0;">Particulars</th>
                <th style="text-align: left; padding: 0 6px 6px 0; width: 100px;">Category</th>
                <th class="right" style="padding: 0 0 6px 6px; width: 70px;">Income</th>
                <th class="right" style="padding: 0 0 6px 6px; width: 70px;">Expense</th>
                <th class="right" style="padding: 0 0 6px 6px; width: 80px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td class="muted" style="padding: 6px 6px 6px 0;">{{ $rangeStart->format('d M Y') }}</td>
                <td class="strong" colspan="2" style="padding: 6px 6px 6px 0;">Opening balance</td>
                <td></td>
                <td></td>
                <td class="right strong num" style="padding: 6px 0 6px 6px;">{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @forelse ($statement as $line)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td class="muted" style="padding: 6px 6px 6px 0; vertical-align: top;">{{ $line['date'] }}</td>
                    <td style="padding: 6px 6px 6px 0; vertical-align: top; color: #0f172a;">{{ $line['particulars'] }}</td>
                    <td class="muted" style="padding: 6px 6px 6px 0; vertical-align: top; font-size: 10px;">{{ $line['category'] }}</td>
                    <td class="right num" style="padding: 6px 0 6px 6px; vertical-align: top;">
                        {{ $line['income'] > 0 ? number_format($line['income'], 2) : '' }}
                    </td>
                    <td class="right num" style="padding: 6px 0 6px 6px; vertical-align: top;">
                        {{ $line['expense'] > 0 ? number_format($line['expense'], 2) : '' }}
                    </td>
                    <td class="right strong num" style="padding: 6px 0 6px 6px; vertical-align: top;">{{ number_format($line['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding: 8px 0; text-align: center;">No entries in this period</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #1e293b;">
                <td></td>
                <td class="muted uppercase" colspan="2" style="font-size: 10px; font-weight: bold; padding-top: 6px;">Totals</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($totals['income'], 2) }}</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($totals['expense'], 2) }}</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Summary --}}
    <table style="margin-top: 20px;">
        <tr>
            <td style="width: 58%;"></td>
            <td>
                <table>
                    <tr>
                        <td class="muted" style="padding: 2px 0;">Opening balance</td>
                        <td class="right num dark" style="padding: 2px 0;">{{ number_format($openingBalance, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding: 2px 0;">Total income</td>
                        <td class="right num dark" style="padding: 2px 0;">{{ number_format($totals['income'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding: 2px 0;">Total expense</td>
                        <td class="right num dark" style="padding: 2px 0;">{{ number_format($totals['expense'], 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted" style="padding: 2px 0;">Net movement</td>
                        <td class="right num dark" style="padding: 2px 0;">{{ number_format($totals['net'], 2) }}</td>
                    </tr>
                </table>
                <table style="border-top: 2px solid #1e293b; margin-top: 6px;">
                    <tr>
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 10px;">Closing Balance</td>
                        <td class="right strong num" style="font-size: 20px; padding-top: 10px;">{{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Signature --}}
    <table style="margin-top: 50px; border-top: 1px solid #e5e7eb;">
        <tr>
            <td class="muted" style="vertical-align: bottom; padding-top: 20px; font-size: 10px;">
                This is a system generated statement.
            </td>
            <td class="center" style="vertical-align: bottom; padding-top: 60px; width: 220px;">
                <div style="border-top: 2px solid #1e293b; padding-top: 6px;">
                    @if ($signatoryName)
                        <p class="dark strong" style="font-size: 13px;">{{ $signatoryName }}</p>
                        @if ($signatoryDesignation)
                            <p class="muted" style="font-size: 10px; margin-top: 2px;">{{ $signatoryDesignation }}</p>
                        @endif
                    @else
                        <p style="color: #1e293b;">Authorized Signature</p>
                    @endif
                    <p class="muted" style="font-size: 10px; margin-top: 2px;">For {{ $companyName }}</p>
                </div>
            </td>
        </tr>
    </table>
</x-pdf-layout>
