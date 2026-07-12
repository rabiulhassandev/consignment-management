@php
    use App\Enums\TransactionType;
@endphp

<x-admin-layout title="Income & Expense">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Income &amp; Expense</h1>
            <p class="mt-1 text-sm text-gray-500">Company income, expense and cash at a glance.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-button variant="secondary" icon="chart-bar" :href="route('admin.income-expense.report')">Reports</x-button>
            @can('transactions.create')
                <x-button icon="plus" :href="route('admin.transactions.index')">Add Entry</x-button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-stat-card label="Income (This Month)" :value="number_format($thisMonth['income'], 2)" icon="arrow-trending-up" color="emerald" />
        <x-stat-card label="Expense (This Month)" :value="number_format($thisMonth['expense'], 2)" icon="arrow-trending-down" color="rose" />
        <x-stat-card label="Cash (This Month)" :value="number_format($thisMonth['cash'], 2)" icon="wallet" :color="$thisMonth['cash'] < 0 ? 'amber' : 'indigo'" />
        <x-stat-card label="Income (All Time)" :value="number_format($allTime['income'], 2)" icon="arrow-trending-up" color="emerald" />
        <x-stat-card label="Expense (All Time)" :value="number_format($allTime['expense'], 2)" icon="arrow-trending-down" color="rose" />
        <x-stat-card label="Cash (All Time)" :value="number_format($allTime['cash'], 2)" icon="wallet" :color="$allTime['cash'] < 0 ? 'amber' : 'indigo'" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Last 6 Months">
            <x-slot:actions>
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="size-2 rounded-full bg-emerald-500"></span> Income
                </span>
                <span class="flex items-center gap-1.5 text-xs text-gray-500">
                    <span class="size-2 rounded-full bg-rose-500"></span> Expense
                </span>
            </x-slot:actions>

            <div class="space-y-5">
                @foreach ($monthlySeries as $month)
                    <div class="flex items-center gap-4">
                        <p class="w-16 shrink-0 text-sm text-gray-500">{{ $month['label'] }}</p>
                        <div class="flex-1 space-y-1.5">
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-emerald-500"
                                         style="width: {{ $monthlyMax > 0 ? round($month['income'] / $monthlyMax * 100, 1) : 0 }}%"></div>
                                </div>
                                <p class="w-24 shrink-0 text-right text-xs tabular-nums text-emerald-600">{{ number_format($month['income'], 2) }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-rose-500"
                                         style="width: {{ $monthlyMax > 0 ? round($month['expense'] / $monthlyMax * 100, 1) : 0 }}%"></div>
                                </div>
                                <p class="w-24 shrink-0 text-right text-xs tabular-nums text-rose-600">{{ number_format($month['expense'], 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card title="Recent Transactions" :flush="true">
            <x-slot:actions>
                <a href="{{ route('admin.transactions.index') }}" class="text-sm font-medium text-indigo-600 transition-colors hover:text-indigo-700">View all</a>
            </x-slot:actions>

            @if ($recentTransactions->isEmpty())
                <x-empty-state icon="banknotes" title="No transactions yet"
                               description="Income and expense entries will appear here." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($recentTransactions as $transaction)
                        <li class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $transaction->category->name }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ $transaction->transaction_date->format('d M Y') }}@if ($transaction->description) · {{ $transaction->description }}@endif
                                </p>
                            </div>
                            <p @class([
                                'shrink-0 text-sm font-medium tabular-nums',
                                'text-emerald-600' => $transaction->type === TransactionType::Income,
                                'text-rose-600' => $transaction->type === TransactionType::Expense,
                            ])>
                                {{ $transaction->type === TransactionType::Income ? '+' : '−' }}{{ number_format((float) $transaction->amount, 2) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-admin-layout>
