@php
    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
@endphp

<x-print-layout :title="'Income & Expense Statement — '.$periodLabel" :back-url="route('admin.income-expense.report', request()->query())">
    <x-slot:letterhead>
        <x-print-letterhead heading="Statement" />
    </x-slot:letterhead>

    <div class="mb-10 flex items-end justify-between gap-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">Statement Period</p>
            <p class="mt-1.5 text-lg font-semibold text-gray-900">{{ $periodLabel }}</p>
            <p class="mt-0.5 text-xs text-gray-500">{{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</p>
        </div>
        <dl class="text-right text-sm">
            <div class="flex justify-end gap-6">
                <dt class="text-gray-400">Report Type</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $periodName }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Statement Date</dt>
                <dd class="w-28 font-medium text-gray-900">{{ now()->format('d M Y') }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Entries</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $statement->count() }}</dd>
            </div>
        </dl>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b-2 border-gray-900 text-[11px] uppercase tracking-widest text-gray-500">
                <th class="pb-2.5 pr-3 text-left font-semibold">{{ $period === 'yearly' ? 'Month' : 'Date' }}</th>
                <th class="pb-2.5 pr-3 text-left font-semibold">Particulars</th>
                <th class="pb-2.5 pr-3 text-left font-semibold">Category</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Income</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Expense</th>
                <th class="pb-2.5 pl-3 text-right font-semibold">Balance</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr>
                <td class="whitespace-nowrap py-2.5 pr-3 text-gray-500">{{ $rangeStart->format('d M Y') }}</td>
                <td class="py-2.5 pr-3 font-medium text-gray-900" colspan="2">Opening balance</td>
                <td class="py-2.5 pl-3"></td>
                <td class="py-2.5 pl-3"></td>
                <td class="py-2.5 pl-3 text-right font-semibold tabular-nums {{ $openingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($openingBalance, 2) }}
                </td>
            </tr>
            @forelse ($statement as $line)
                <tr>
                    <td class="whitespace-nowrap py-2.5 pr-3 text-gray-900">{{ $line['date'] }}</td>
                    <td class="py-2.5 pr-3 text-gray-900">{{ $line['particulars'] }}</td>
                    <td class="py-2.5 pr-3 text-gray-500">{{ $line['category'] }}</td>
                    <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                        {{ $line['income'] > 0 ? number_format($line['income'], 2) : '' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                        {{ $line['expense'] > 0 ? number_format($line['expense'], 2) : '' }}
                    </td>
                    <td class="py-2.5 pl-3 text-right font-medium tabular-nums {{ $line['balance'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ number_format($line['balance'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-3 text-center text-gray-400">No entries in this period</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="border-t-2 border-gray-900 text-sm">
                <td class="pt-3 pr-3"></td>
                <td class="pt-3 pr-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500" colspan="2">Totals</td>
                <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['income'], 2) }}</td>
                <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['expense'], 2) }}</td>
                <td class="pt-3 pl-3 text-right font-semibold tabular-nums {{ $closingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($closingBalance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-8 flex justify-end">
        <dl class="w-72 text-sm">
            <div class="flex justify-between gap-6 py-1">
                <dt class="text-gray-500">Opening balance</dt>
                <dd class="tabular-nums text-gray-900">{{ number_format($openingBalance, 2) }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-1">
                <dt class="text-gray-500">Total income</dt>
                <dd class="tabular-nums text-gray-900">{{ number_format($totals['income'], 2) }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-1">
                <dt class="text-gray-500">Total expense</dt>
                <dd class="tabular-nums text-gray-900">{{ number_format($totals['expense'], 2) }}</dd>
            </div>
            <div class="flex justify-between gap-6 py-1">
                <dt class="text-gray-500">Net movement</dt>
                <dd class="tabular-nums {{ $totals['net'] < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($totals['net'], 2) }}</dd>
            </div>
            <div class="mt-1 flex justify-between gap-6 border-t-2 border-gray-900 pt-3">
                <dt class="text-[11px] font-semibold uppercase tracking-widest text-gray-500">Closing Balance</dt>
                <dd class="text-base font-semibold tabular-nums {{ $closingBalance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($closingBalance, 2) }}
                </dd>
            </div>
        </dl>
    </div>

    <div class="mt-16 flex items-end justify-between gap-10 border-t border-gray-200 pt-6">
        <p class="text-[11px] text-gray-400">This is a system generated statement.</p>
        <div class="shrink-0 text-center" style="margin-top: 50px">
            <div class="w-52 border-t border-gray-900 pt-2 text-sm text-gray-700">Authorized Signature</div>
            <p class="mt-1 text-[11px] text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>
</x-print-layout>
