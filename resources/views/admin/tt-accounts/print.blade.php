@php
    use App\Enums\EntryType;

    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
@endphp

<x-print-layout :title="$ttAccount->title" :back-url="route('admin.tt-accounts.show', $ttAccount)">
    <x-slot:letterhead>
        <x-print-letterhead heading="Statement" />
    </x-slot:letterhead>

    <div class="mb-10 flex items-end justify-between gap-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Account</p>
            <p class="mt-1.5 text-lg font-semibold text-gray-900">{{ $ttAccount->title }}</p>
            <p class="mt-0.5 text-sm text-gray-500">{{ $ttAccount->customer->name }}</p>
        </div>
        <dl class="text-right text-sm">
            <div class="flex justify-end gap-6">
                <dt class="text-gray-400">Currency</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $ttAccount->currency->code }} ({{ $ttAccount->currency->symbol }})</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Status</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $ttAccount->status->label() }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Printed</dt>
                <dd class="w-28 font-medium text-gray-900">{{ now()->format('d M Y') }}</dd>
            </div>
        </dl>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b-2 border-gray-900 text-[11px] uppercase tracking-widest text-gray-500">
                <th class="pb-2.5 pr-3 text-left font-semibold">Date</th>
                <th class="pb-2.5 pr-3 text-left font-semibold">Description</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Received</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Paid</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Balance</th>
                <th class="pb-2.5 pl-4 text-left font-semibold">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @if ($ttAccount->opening_balance !== null)
                <tr>
                    <td class="py-2.5 pr-3 text-gray-400">—</td>
                    <td class="py-2.5 pr-3 font-medium text-gray-900">Opening balance</td>
                    <td class="py-2.5 pl-3"></td>
                    <td class="py-2.5 pl-3"></td>
                    <td class="py-2.5 pl-3 text-right font-medium tabular-nums text-gray-900">
                        {{ number_format((float) $ttAccount->opening_balance, 2) }}
                    </td>
                    <td class="py-2.5 pl-4"></td>
                </tr>
            @endif
            @forelse ($entries as $entry)
                <tr>
                    <td class="whitespace-nowrap py-2.5 pr-3 text-gray-600">{{ $entry->entry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="py-2.5 pr-3">
                        <p class="text-gray-900">{{ $entry->description }}</p>
                        @if ($entry->source_amount !== null)
                            <p class="text-[11px] text-gray-400">
                                {{ $entry->sourceCurrency?->code ?? '' }} {{ number_format((float) $entry->source_amount, 2) }}@if ($entry->source_rate !== null) @ {{ (float) $entry->source_rate }}@endif
                            </p>
                        @endif
                    </td>
                    <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                        {{ $entry->type === EntryType::Received ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                        {{ $entry->type === EntryType::Paid ? number_format((float) $entry->amount, 2) : '' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right font-medium tabular-nums text-gray-900">
                        {{ number_format($entry->running_balance, 2) }}
                    </td>
                    <td class="py-2.5 pl-4 text-[11px] text-gray-500">{{ $entry->remarks ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-3 text-center text-gray-400">No entries</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-900 text-sm">
                <td class="pt-3 pr-3"></td>
                <td class="pt-3 pr-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">Totals</td>
                <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totalReceived, 2) }}</td>
                <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totalPaid, 2) }}</td>
                <td class="pt-3 pl-3 text-right text-base font-semibold tabular-nums text-gray-900">{{ number_format($closingBalance, 2) }}</td>
                <td class="pt-3 pl-4"></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 flex justify-end">
        <p class="text-[11px] text-gray-400">
            Closing balance: <span class="font-semibold text-gray-900">{{ $ttAccount->currency->symbol }}{{ number_format($closingBalance, 2) }} {{ $ttAccount->currency->code }}</span>
        </p>
    </div>

    <div class="mt-16 flex items-end justify-end gap-10 border-t border-gray-200 pt-6">
        <div class="shrink-0 text-center" style="margin-top: 50px">
            <div class="w-52 border-t border-gray-900 pt-2 text-sm text-gray-700">Authorized Signature</div>
            <p class="mt-1 text-[11px] text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>
</x-print-layout>
