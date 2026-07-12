@php
    use App\Enums\TransactionType;

    $companyName = \App\Models\Setting::get('company_name') ?: \App\Models\Setting::get('site_name', 'BNoor Group');
    $periodName = ['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly'][$period];
@endphp

<x-print-layout :title="'Income & Expense Report — '.$periodLabel" :back-url="route('admin.income-expense.report', request()->query())">
    <x-slot:letterhead>
        <x-print-letterhead heading="Report" />
    </x-slot:letterhead>

    <div class="mb-10 flex items-end justify-between gap-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400">{{ $periodName }} Report</p>
            <p class="mt-1.5 text-lg font-semibold text-gray-900">{{ $periodLabel }}</p>
        </div>
        <dl class="text-right text-sm">
            <div class="flex justify-end gap-6">
                <dt class="text-gray-400">Period</dt>
                <dd class="w-28 font-medium text-gray-900">{{ $periodName }}</dd>
            </div>
            <div class="mt-1 flex justify-end gap-6">
                <dt class="text-gray-400">Printed</dt>
                <dd class="w-28 font-medium text-gray-900">{{ now()->format('d M Y') }}</dd>
            </div>
        </dl>
    </div>

    @if ($period === 'daily')
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-900 text-[11px] uppercase tracking-widest text-gray-500">
                    <th class="pb-2.5 pr-3 text-left font-semibold">Category</th>
                    <th class="pb-2.5 pr-3 text-left font-semibold">Description</th>
                    <th class="pb-2.5 pl-3 text-right font-semibold">Income</th>
                    <th class="pb-2.5 pl-3 text-right font-semibold">Expense</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="py-2.5 pr-3 font-medium text-gray-900">{{ $entry->category->name }}</td>
                        <td class="py-2.5 pr-3 text-gray-600">{{ $entry->description ?? '—' }}</td>
                        <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                            {{ $entry->type === TransactionType::Income ? number_format((float) $entry->amount, 2) : '' }}
                        </td>
                        <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">
                            {{ $entry->type === TransactionType::Expense ? number_format((float) $entry->amount, 2) : '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-3 text-center text-gray-400">No entries</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-900 text-sm">
                    <td class="pt-3 pr-3"></td>
                    <td class="pt-3 pr-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">Totals</td>
                    <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['income'], 2) }}</td>
                    <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['expense'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-gray-900 text-[11px] uppercase tracking-widest text-gray-500">
                    <th class="pb-2.5 pr-3 text-left font-semibold">{{ $period === 'monthly' ? 'Date' : 'Month' }}</th>
                    <th class="pb-2.5 pl-3 text-right font-semibold">Income</th>
                    <th class="pb-2.5 pl-3 text-right font-semibold">Expense</th>
                    <th class="pb-2.5 pl-3 text-right font-semibold">Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($rows as $row)
                    <tr>
                        <td class="whitespace-nowrap py-2.5 pr-3 text-gray-900">{{ $row['label'] }}</td>
                        <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">{{ number_format($row['income'], 2) }}</td>
                        <td class="py-2.5 pl-3 text-right tabular-nums text-gray-900">{{ number_format($row['expense'], 2) }}</td>
                        <td class="py-2.5 pl-3 text-right font-medium tabular-nums {{ $row['net'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            {{ number_format($row['net'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-3 text-center text-gray-400">No entries</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-gray-900 text-sm">
                    <td class="pt-3 pr-3 text-[11px] font-semibold uppercase tracking-widest text-gray-500">Totals</td>
                    <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['income'], 2) }}</td>
                    <td class="pt-3 pl-3 text-right font-semibold tabular-nums text-gray-900">{{ number_format($totals['expense'], 2) }}</td>
                    <td class="pt-3 pl-3 text-right text-base font-semibold tabular-nums {{ $totals['net'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ number_format($totals['net'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="mt-8 flex justify-end">
        <p class="text-[11px] text-gray-400">
            Net {{ strtolower($periodName) }} balance: <span class="font-semibold text-gray-900">{{ number_format($totals['net'], 2) }}</span>
        </p>
    </div>

    <div class="mt-16 flex items-end justify-end gap-10 border-t border-gray-200 pt-6">
        <div class="shrink-0 text-center" style="margin-top: 50px">
            <div class="w-52 border-t border-gray-900 pt-2 text-sm text-gray-700">Authorized Signature</div>
            <p class="mt-1 text-[11px] text-gray-400">For {{ $companyName }}</p>
        </div>
    </div>
</x-print-layout>
