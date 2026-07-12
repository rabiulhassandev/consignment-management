@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
@endphp

<x-print-layout :title="'LC Bill '.$lcBill->bill_no" :back-url="route('admin.lc-bills.show', $lcBill)">
    <x-slot:letterhead>
        <x-print-letterhead heading="LC Bill" />
    </x-slot:letterhead>

    <div class="mb-10 flex items-end justify-between gap-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Billed To</p>
            <p class="mt-1.5 text-lg font-semibold text-gray-900">{{ $lcBill->customer->name }}</p>
            @if ($lcBill->shipment_title)
                <p class="mt-0.5 text-sm text-gray-500">{{ $lcBill->shipment_title }}</p>
            @endif
        </div>
        <dl class="text-right text-sm">
            <div class="flex justify-end gap-6">
                <dt class="text-gray-400">Bill No</dt>
                <dd class="w-32 font-medium text-gray-900">{{ $lcBill->bill_no }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Bill Date</dt>
                <dd class="w-32 font-medium text-gray-900">{{ $lcBill->bill_date->format('d M Y') }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">LC Number</dt>
                <dd class="w-32 font-medium text-gray-900">{{ $lcBill->lc_number }}</dd>
            </div>
            @if ($lcBill->lc_value !== null)
                <div class="mt-1 flex justify-end gap-6">
                    <dt class="text-gray-400">LC Value</dt>
                    <dd class="w-32 font-medium tabular-nums text-gray-900">{{ number_format((float) $lcBill->lc_value, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
            @endif
            @if ($lcBill->ci_value !== null)
                <div class="mt-1 flex justify-end gap-6">
                    <dt class="text-gray-400">CI Value</dt>
                    <dd class="w-32 font-medium tabular-nums text-gray-900">{{ number_format((float) $lcBill->ci_value, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="grid grid-cols-2 gap-10">
        <div>
            <p class="border-b-2 border-gray-900 pb-2 text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                Received ({{ $lcBill->currency->code }})
            </p>
            <div class="divide-y divide-gray-200 text-sm">
                @forelse ($receipts as $entry)
                    <div class="flex items-start justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-gray-900">{{ $entry->description }}</p>
                            <p class="text-[11px] text-gray-400">
                                @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                @if ($entry->source_amount !== null)
                                    @if ($entry->entry_date) · @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                @endif
                            </p>
                        </div>
                        <p class="shrink-0 font-medium tabular-nums text-gray-900">{{ number_format((float) $entry->amount, 2) }}</p>
                    </div>
                @empty
                    <p class="py-2.5 text-sm text-gray-400">No entries</p>
                @endforelse
            </div>
            <div class="flex items-baseline justify-between border-t-2 border-gray-900 pt-2.5">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-gray-500">Total Received</span>
                <span class="font-semibold tabular-nums text-gray-900">{{ number_format($totalReceived, 2) }}</span>
            </div>
        </div>

        <div>
            <p class="border-b-2 border-gray-900 pb-2 text-[11px] font-semibold uppercase tracking-widest text-gray-500">
                Paid / Expenses ({{ $lcBill->currency->code }})
            </p>
            <div class="divide-y divide-gray-200 text-sm">
                @forelse ($payments as $entry)
                    <div class="flex items-start justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="text-gray-900">{{ $entry->description }}</p>
                            <p class="text-[11px] text-gray-400">
                                @if ($entry->entry_date){{ $entry->entry_date->format('d M Y') }}@endif
                                @if ($entry->source_amount !== null)
                                    @if ($entry->entry_date) · @endif{{ number_format((float) $entry->source_amount, 2) }} / {{ (float) $entry->source_rate }}
                                @endif
                            </p>
                        </div>
                        <p class="shrink-0 font-medium tabular-nums text-gray-900">{{ number_format((float) $entry->amount, 2) }}</p>
                    </div>
                @empty
                    <p class="py-2.5 text-sm text-gray-400">No entries</p>
                @endforelse
            </div>
            <div class="flex items-baseline justify-between border-t-2 border-gray-900 pt-2.5">
                <span class="text-[11px] font-semibold uppercase tracking-widest text-gray-500">Total Paid</span>
                <span class="font-semibold tabular-nums text-gray-900">{{ number_format($totalPaid, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="mt-10 flex justify-end">
        <div class="w-80">
            <dl class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Received</dt>
                    <dd class="tabular-nums text-gray-900">{{ number_format($totalReceived, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total Paid</dt>
                    <dd class="tabular-nums text-gray-900">{{ number_format($totalPaid, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                <div class="flex items-baseline justify-between border-t-2 border-gray-900 pt-2.5">
                    <dt class="text-[11px] font-semibold uppercase tracking-widest text-gray-500">Balance</dt>
                    <dd class="text-lg font-semibold tabular-nums text-gray-900">{{ number_format($balance, 2) }} {{ $lcBill->currency->code }}</dd>
                </div>
                @if ($lcBill->conversion_rate !== null)
                    <div class="flex justify-between pt-1">
                        <dt class="text-gray-500">Bank Rate</dt>
                        <dd class="tabular-nums text-gray-900">{{ (float) $lcBill->conversion_rate }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-gray-300 pt-2">
                        <dt class="text-[11px] font-bold uppercase tracking-widest text-gray-900">
                            {{ $lcBill->is_settled ? 'Settled' : 'Due' }}
                        </dt>
                        <dd class="text-2xl font-semibold tabular-nums text-gray-900">৳{{ number_format($localDue, 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="mt-16 flex items-end justify-end gap-10 border-t border-gray-200 pt-6">
        <div class="shrink-0 text-center" style="margin-top: 50px">
            <div class="w-52 border-t border-gray-900 pt-2 text-sm text-gray-700">Authorized Signature</div>
            <p class="mt-1 text-[11px] text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>
</x-print-layout>
