@php
    use App\Enums\EntryType;

    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $code = $ttAccount->currency->code;
    $symbol = $ttAccount->currency->symbol;
@endphp

<x-pdf-layout heading="Statement">
    {{-- Account + statement meta --}}
    <table style="margin-top: 18px;">
        <tr>
            <td style="vertical-align: top;">
                <p class="muted uppercase" style="font-size: 10px; font-weight: bold;">Account</p>
                <p class="strong" style="font-size: 17px; margin-top: 3px;">{{ $ttAccount->title }}</p>
                <p class="muted" style="margin-top: 2px;">{{ $ttAccount->customer->name }}</p>
            </td>
            <td class="right" style="vertical-align: top; width: 240px;">
                <table>
                    <tr>
                        <td class="muted">Currency</td>
                        <td class="right strong">{{ $ttAccount->currency->code }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Status</td>
                        <td class="right strong">{{ $ttAccount->status->label() }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Printed</td>
                        <td class="right strong">{{ now()->format('d M Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Statement --}}
    <table style="margin-top: 26px;">
        <thead>
            <tr class="muted uppercase" style="font-size: 9px; border-bottom: 2px solid #1e293b;">
                <th style="text-align: left; padding: 0 6px 6px 0;">Date</th>
                <th style="text-align: left; padding: 0 6px 6px 0;">Description</th>
                <th class="right" style="padding: 0 0 6px 6px;">Received</th>
                <th class="right" style="padding: 0 0 6px 6px;">Paid</th>
                <th class="right" style="padding: 0 0 6px 6px;">Balance</th>
                <th style="text-align: left; padding: 0 0 6px 8px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @if ($ttAccount->opening_balance !== null)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td class="muted" style="padding: 6px 6px 6px 0;">—</td>
                    <td class="strong" style="padding: 6px 6px 6px 0;">Opening balance</td>
                    <td></td>
                    <td></td>
                    <td class="right strong num" style="padding: 6px 0;">{{ number_format((float) $ttAccount->opening_balance, 2) }}</td>
                    <td></td>
                </tr>
            @endif
            @forelse ($entries as $entry)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td class="muted" style="padding: 6px 6px 6px 0; vertical-align: top;">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                    <td style="padding: 6px 6px 6px 0; vertical-align: top;">
                        <span style="color: #0f172a;">{{ $entry->description }}</span>
                        @if ($entry->source_amount !== null)
                            <br><span class="muted" style="font-size: 9px;">
                                {{ $entry->sourceCurrency?->code ?? '' }} {{ number_format((float) $entry->source_amount, 2) }}@if ($entry->source_rate !== null) @ {{ (float) $entry->source_rate }}@endif
                            </span>
                        @endif
                    </td>
                    <td class="right num" style="padding: 6px 0 6px 6px; vertical-align: top;">
                        {{ $entry->type === EntryType::Received ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="right num" style="padding: 6px 0 6px 6px; vertical-align: top;">
                        {{ $entry->type === EntryType::Paid ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="right strong num" style="padding: 6px 0 6px 6px; vertical-align: top;">{{ number_format($entry->running_balance, 2) }}</td>
                    <td class="muted" style="padding: 6px 0 6px 8px; font-size: 9px; vertical-align: top;">{{ $entry->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding: 8px 0; text-align: center;">No entries</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #1e293b;">
                <td></td>
                <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 6px;">Totals</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($totalReceived, 2) }}</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($totalPaid, 2) }}</td>
                <td class="right strong num" style="padding-top: 6px;">{{ number_format($closingBalance, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Closing balance --}}
    <table style="margin-top: 18px;">
        <tr>
            <td style="width: 55%;"></td>
            <td>
                <table style="border-top: 2px solid #1e293b;">
                    <tr>
                        <td class="muted uppercase" style="font-size: 10px; font-weight: bold; padding-top: 10px;">Closing Balance</td>
                        <td class="right strong num" style="font-size: 20px; padding-top: 10px;"><span class="cjk">{{ $symbol }}</span>{{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </table>
                <p class="right muted" style="font-size: 10px; margin-top: 4px;">
                    Balance in {{ $ttAccount->currency->name }} ({{ $ttAccount->currency->code }})
                </p>
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
