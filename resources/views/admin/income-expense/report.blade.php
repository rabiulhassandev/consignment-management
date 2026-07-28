<x-admin-layout title="Income & Expense Report">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Reports</h1>
            <p class="mt-1 text-sm text-gray-500">Daily, monthly, yearly and custom date range income &amp; expense statements.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-button variant="secondary" icon="printer" :href="route('admin.income-expense.report.print', request()->query())" target="_blank">
                Print
            </x-button>
            <x-button variant="secondary" icon="arrow-down-tray" :href="route('admin.income-expense.report.pdf', request()->query())">
                Download PDF
            </x-button>
            <x-button variant="secondary" icon="document-arrow-down" :href="route('admin.income-expense.report.excel', request()->query())">
                Download Excel
            </x-button>
        </div>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.income-expense.report') }}" x-data="{ period: @js($period) }"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label for="period" class="mb-1.5 block text-sm font-medium text-gray-700">Period</label>
                <select id="period" name="period" x-model="period"
                        class="block w-40 rounded-lg border-0 py-2 pl-3 pr-8 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="range">Custom Range</option>
                </select>
            </div>
            <div x-cloak x-show="period === 'daily'">
                <label for="report-date" class="mb-1.5 block text-sm font-medium text-gray-700">Date</label>
                <input id="report-date" type="date" name="date" value="{{ $selectedDate }}" :disabled="period !== 'daily'"
                       class="block w-44 rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <div x-cloak x-show="period === 'monthly'">
                <label for="report-month" class="mb-1.5 block text-sm font-medium text-gray-700">Month</label>
                <input id="report-month" type="month" name="month" value="{{ $selectedMonth }}" :disabled="period !== 'monthly'"
                       class="block w-44 rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <div x-cloak x-show="period === 'yearly'">
                <label for="report-year" class="mb-1.5 block text-sm font-medium text-gray-700">Year</label>
                <input id="report-year" type="number" name="year" value="{{ $selectedYear }}" min="2000" max="2100" :disabled="period !== 'yearly'"
                       class="block w-32 rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <div x-cloak x-show="period === 'range'">
                <label for="report-date-from" class="mb-1.5 block text-sm font-medium text-gray-700">From</label>
                <input id="report-date-from" type="date" name="date_from" value="{{ $selectedFrom }}" :disabled="period !== 'range'"
                       class="block w-44 rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <div x-cloak x-show="period === 'range'">
                <label for="report-date-to" class="mb-1.5 block text-sm font-medium text-gray-700">To</label>
                <input id="report-date-to" type="date" name="date_to" value="{{ $selectedTo }}" :disabled="period !== 'range'"
                       class="block w-44 rounded-lg border-0 px-3 py-2 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>
            <x-button type="submit">Apply</x-button>
        </form>
        @error('date_to')
            <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </x-card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Income — {{ $periodLabel }}" :value="number_format($totals['income'], 2)" icon="arrow-trending-up" color="emerald" />
        <x-stat-card label="Expense — {{ $periodLabel }}" :value="number_format($totals['expense'], 2)" icon="arrow-trending-down" color="rose" />
        <x-stat-card label="Net — {{ $periodLabel }}" :value="number_format($totals['net'], 2)" icon="wallet" :color="$totals['net'] < 0 ? 'amber' : 'indigo'" />
        <x-stat-card label="Closing Balance" :value="number_format($closingBalance, 2)" icon="banknotes" :color="$closingBalance < 0 ? 'amber' : 'sky'" />
    </div>

    <x-card :flush="true" class="mt-6" title="Statement — {{ $periodLabel }}">
        <x-slot:actions>
            <p class="text-xs text-gray-500">
                Opening balance <span class="font-semibold tabular-nums text-gray-900">{{ number_format($openingBalance, 2) }}</span>
            </p>
        </x-slot:actions>

        @if ($statement->isEmpty())
            <x-empty-state icon="chart-bar" title="No entries in this period"
                           description="Pick another period or add entries from the Transactions page." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50/75">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-3 sm:px-6">{{ $period === 'yearly' ? 'Month' : 'Date' }}</th>
                            <th class="px-4 py-3">Particulars</th>
                            @if ($entries === null)
                                <th class="hidden w-44 px-4 py-3 lg:table-cell"></th>
                            @else
                                <th class="px-4 py-3">Category</th>
                            @endif
                            <th class="px-4 py-3 text-right">Income</th>
                            <th class="px-4 py-3 text-right">Expense</th>
                            <th class="px-4 py-3 text-right sm:px-6">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50/40">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500 sm:px-6">{{ $rangeStart->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">Opening balance</td>
                            @if ($entries === null)
                                <td class="hidden w-44 px-4 py-3 lg:table-cell"></td>
                            @else
                                <td class="px-4 py-3"></td>
                            @endif
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                            <td @class([
                                'whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums sm:px-6',
                                'text-gray-900' => $openingBalance >= 0,
                                'text-red-600' => $openingBalance < 0,
                            ])>
                                {{ number_format($openingBalance, 2) }}
                            </td>
                        </tr>
                        @foreach ($statement as $line)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 sm:px-6">{{ $line['date'] }}</td>
                                <td class="max-w-xs truncate px-4 py-3 text-gray-600">{{ $line['particulars'] }}</td>
                                @if ($entries === null)
                                    <td class="hidden w-44 px-4 py-3 lg:table-cell">
                                        <div class="space-y-1">
                                            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-1.5 rounded-full bg-emerald-500"
                                                     style="width: {{ $reportMax > 0 ? round($line['income'] / $reportMax * 100, 1) : 0 }}%"></div>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-1.5 rounded-full bg-rose-500"
                                                     style="width: {{ $reportMax > 0 ? round($line['expense'] / $reportMax * 100, 1) : 0 }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $line['category'] }}</td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-emerald-600">
                                    {{ $line['income'] > 0 ? number_format($line['income'], 2) : '' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-rose-600">
                                    {{ $line['expense'] > 0 ? number_format($line['expense'], 2) : '' }}
                                </td>
                                <td @class([
                                    'whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums sm:px-6',
                                    'text-gray-900' => $line['balance'] >= 0,
                                    'text-red-600' => $line['balance'] < 0,
                                ])>
                                    {{ number_format($line['balance'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50/75">
                        <tr class="text-sm font-semibold text-gray-900">
                            <td class="px-4 py-3 text-xs uppercase tracking-wider text-gray-500 sm:px-6">Totals</td>
                            <td class="px-4 py-3"></td>
                            @if ($entries === null)
                                <td class="hidden w-44 px-4 py-3 lg:table-cell"></td>
                            @else
                                <td class="px-4 py-3"></td>
                            @endif
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-emerald-600">{{ number_format($totals['income'], 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-rose-600">{{ number_format($totals['expense'], 2) }}</td>
                            <td @class([
                                'whitespace-nowrap px-4 py-3 text-right tabular-nums sm:px-6',
                                'text-red-600' => $closingBalance < 0,
                            ])>
                                {{ number_format($closingBalance, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-card>
</x-admin-layout>
