@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
@endphp

<x-print-page :title="'LC Bill '.$lcBill->bill_no" :back-url="route('admin.lc-bills.show', $lcBill)" heading="LC Bill">
    {{-- Billed to + bill meta --}}
    <div class="mt-9 flex items-start justify-between gap-8">
        <div>
            <p class="text-[13px] font-semibold uppercase tracking-[0.2em] text-gray-400">Billed To</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900">{{ $lcBill->customer->name }}</p>
            @if ($lcBill->shipment_title)
                <p class="mt-1 text-sm text-gray-500">{{ $lcBill->shipment_title }}</p>
            @endif
        </div>
        <dl class="shrink-0 space-y-1.5 text-[15px]">
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Bill No</dt>
                <dd class="w-36 text-right font-bold tabular-nums text-slate-800">{{ $lcBill->bill_no }}</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Bill Date</dt>
                <dd class="w-36 text-right font-bold text-slate-800">{{ $lcBill->bill_date->format('d M Y') }}</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">LC Number</dt>
                <dd class="w-36 text-right font-bold tabular-nums text-slate-800">{{ $lcBill->lc_number }}</dd>
            </div>
            @if ($lcBill->lc_value !== null)
                <div class="flex items-baseline justify-end gap-8">
                    <dt class="tracking-wide text-gray-400">LC Value</dt>
                    <dd class="w-36 text-right font-bold tabular-nums text-slate-800">{{ number_format((float) $lcBill->lc_value, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
            @endif
            @if ($lcBill->ci_value !== null)
                <div class="flex items-baseline justify-end gap-8">
                    <dt class="tracking-wide text-gray-400">CI Value</dt>
                    <dd class="w-36 text-right font-bold tabular-nums text-slate-800">{{ number_format((float) $lcBill->ci_value, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
            @endif
        </dl>
    </div>

    {{-- Received / Paid ledger --}}
    <div class="mt-12 grid grid-cols-2 gap-10">
        <div>
            <p class="border-b-[3px] border-slate-800 pb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                Received ({{ $lcBill->currency->code }})
            </p>
            <div class="divide-y divide-gray-200 text-sm">
                @forelse ($receipts as $entry)
                    <div class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-slate-900">{{ $entry->description }}</p>
                            <p class="text-[11px] text-gray-400">
                                @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                @if ($entry->source_amount !== null)
                                    @if ($entry->entry_date) · @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                @endif
                            </p>
                        </div>
                        <p class="shrink-0 font-bold tabular-nums text-slate-900">{{ number_format((float) $entry->amount, 2) }}</p>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">No entries</p>
                @endforelse
            </div>
            <div class="flex items-baseline justify-between border-t-2 border-slate-800 pt-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Total Received</span>
                <span class="font-bold tabular-nums text-slate-900">{{ number_format($totalReceived, 2) }}</span>
            </div>
        </div>

        <div>
            <p class="border-b-[3px] border-slate-800 pb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
                Paid / Expenses ({{ $lcBill->currency->code }})
            </p>
            <div class="divide-y divide-gray-200 text-sm">
                @forelse ($payments as $entry)
                    <div class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-slate-900">{{ $entry->description }}</p>
                            <p class="text-[11px] text-gray-400">
                                @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                @if ($entry->source_amount !== null)
                                    @if ($entry->entry_date) · @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                @endif
                            </p>
                        </div>
                        <p class="shrink-0 font-bold tabular-nums text-slate-900">{{ number_format((float) $entry->amount, 2) }}</p>
                    </div>
                @empty
                    <p class="py-3 text-sm text-gray-400">No entries</p>
                @endforelse
            </div>
            <div class="flex items-baseline justify-between border-t-2 border-slate-800 pt-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Total Paid</span>
                <span class="font-bold tabular-nums text-slate-900">{{ number_format($totalPaid, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Settlement summary --}}
    <div class="mt-10 flex justify-end">
        <div class="w-88">
            <dl class="space-y-1.5 text-[15px]">
                <div class="flex items-baseline justify-between">
                    <dt class="tracking-wide text-gray-400">Total Received</dt>
                    <dd class="tabular-nums text-slate-800">{{ number_format($totalReceived, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                <div class="flex items-baseline justify-between">
                    <dt class="tracking-wide text-gray-400">Total Paid</dt>
                    <dd class="tabular-nums text-slate-800">{{ number_format($totalPaid, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                <div class="flex items-baseline justify-between border-t-2 border-slate-800 pt-3">
                    <dt class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Balance</dt>
                    <dd class="text-xl font-bold tabular-nums text-slate-900">{{ number_format($balance, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                @if ($lcBill->conversion_rate !== null)
                    <div class="flex items-baseline justify-between pt-1">
                        <dt class="tracking-wide text-gray-400">Bank Rate</dt>
                        <dd class="tabular-nums text-slate-800">{{ (float) $lcBill->conversion_rate }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-gray-300 pt-3">
                        <dt class="text-xs font-bold uppercase tracking-[0.3em] text-slate-900">
                            {{ $lcBill->is_settled ? 'Settled' : 'Due' }}
                        </dt>
                        <dd class="text-3xl font-bold tabular-nums text-slate-900">৳{{ number_format($localDue, 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Signature --}}
    <div class="mt-16 flex items-end justify-end gap-10 border-t border-gray-200 pt-6">
        <div class="shrink-0 text-center" style="margin-top: 50px">
            <div class="w-64 border-t-2 border-slate-800"></div>
            <p class="mt-2 text-base text-slate-800">Authorized Signature</p>
            <p class="mt-0.5 text-xs text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>
</x-print-page>
