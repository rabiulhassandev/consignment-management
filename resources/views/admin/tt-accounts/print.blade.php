@php
    use App\Enums\EntryType;

    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
@endphp

<x-print-page :title="$ttAccount->title" :back-url="route('admin.tt-accounts.show', $ttAccount)" heading="Statement">
    {{-- Account + statement meta --}}
    <div class="mt-9 flex items-start justify-between gap-8">
        <div>
            <p class="text-[13px] font-semibold uppercase tracking-[0.2em] text-gray-400">Account</p>
            <p class="mt-1.5 text-2xl font-bold tracking-tight text-slate-900">{{ $ttAccount->title }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ $ttAccount->customer->name }}</p>
        </div>
        <dl class="shrink-0 space-y-1.5 text-[15px]">
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Currency</dt>
                <dd class="w-28 text-right font-bold text-slate-800">{{ $ttAccount->currency->code }} ({{ $ttAccount->currency->symbol }})</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Status</dt>
                <dd class="w-28 text-right font-bold text-slate-800">{{ $ttAccount->status->label() }}</dd>
            </div>
            <div class="flex items-baseline justify-end gap-8">
                <dt class="tracking-wide text-gray-400">Printed</dt>
                <dd class="w-28 text-right font-bold text-slate-800">{{ now()->format('d M Y') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Statement --}}
    <table class="mt-12 w-full text-sm">
        <thead>
            <tr class="border-b-[3px] border-slate-800 text-xs uppercase tracking-[0.15em] text-gray-400">
                <th class="pb-3 pr-3 text-left font-semibold">Date</th>
                <th class="pb-3 pr-3 text-left font-semibold">Description</th>
                <th class="pb-3 pl-3 text-right font-semibold">Received</th>
                <th class="pb-3 pl-3 text-right font-semibold">Paid</th>
                <th class="pb-3 pl-3 text-right font-semibold">Balance</th>
                <th class="pb-3 pl-4 text-left font-semibold">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @if ($ttAccount->opening_balance !== null)
                <tr>
                    <td class="py-3 pr-3 text-gray-400">—</td>
                    <td class="py-3 pr-3 font-medium text-slate-900">Opening balance</td>
                    <td class="py-3 pl-3"></td>
                    <td class="py-3 pl-3"></td>
                    <td class="py-3 pl-3 text-right font-bold tabular-nums text-slate-900">
                        {{ number_format((float) $ttAccount->opening_balance, 2) }}
                    </td>
                    <td class="py-3 pl-4"></td>
                </tr>
            @endif
            @forelse ($entries as $entry)
                <tr>
                    <td class="whitespace-nowrap py-3 pr-3 text-gray-500">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="py-3 pr-3">
                        <p class="text-slate-900">{{ $entry->description }}</p>
                        @if ($entry->source_amount !== null)
                            <p class="text-[11px] text-gray-400">
                                {{ $entry->sourceCurrency?->code ?? '' }} {{ number_format((float) $entry->source_amount, 2) }}@if ($entry->source_rate !== null) @ {{ (float) $entry->source_rate }}@endif
                            </p>
                        @endif
                    </td>
                    <td class="py-3 pl-3 text-right tabular-nums text-slate-800">
                        {{ $entry->type === EntryType::Received ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="py-3 pl-3 text-right tabular-nums text-slate-800">
                        {{ $entry->type === EntryType::Paid ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="py-3 pl-3 text-right font-bold tabular-nums text-slate-900">
                        {{ number_format($entry->running_balance, 2) }}
                    </td>
                    <td class="py-3 pl-4 text-[11px] text-gray-500">{{ $entry->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-3 text-center text-gray-400">No entries</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-slate-800">
                <td class="pt-3 pr-3"></td>
                <td class="pt-3 pr-3 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Totals</td>
                <td class="pt-3 pl-3 text-right font-bold tabular-nums text-slate-900">{{ number_format($totalReceived, 2) }}</td>
                <td class="pt-3 pl-3 text-right font-bold tabular-nums text-slate-900">{{ number_format($totalPaid, 2) }}</td>
                <td class="pt-3 pl-3 text-right text-base font-bold tabular-nums text-slate-900">{{ number_format($closingBalance, 2) }}</td>
                <td class="pt-3 pl-4"></td>
            </tr>
        </tfoot>
    </table>

    {{-- Closing balance --}}
    <div class="mt-8 flex justify-end">
        <div class="w-88">
            <div class="flex items-baseline justify-between border-t-2 border-slate-800 pt-4">
                <span class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Closing Balance</span>
                <span class="text-2xl font-bold tabular-nums text-slate-900">
                    {{ $ttAccount->currency->symbol }}{{ number_format($closingBalance, 2) }}
                </span>
            </div>
            <p class="mt-2 text-right text-xs text-gray-400">
                Balance in {{ $ttAccount->currency->name }} ({{ $ttAccount->currency->code }})
            </p>
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
