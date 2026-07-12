<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncomeExpenseController extends Controller
{
    /**
     * Module overview with income, expense, and cash summaries.
     */
    public function index(): View
    {
        $now = Carbon::now();
        $monthlySeries = $this->monthlySeries($now->copy()->subMonths(5)->startOfMonth(), $now->copy()->endOfMonth());

        return view('admin.income-expense.index', [
            'allTime' => $this->totalsBetween(null, null),
            'thisMonth' => $this->totalsBetween($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'monthlySeries' => $monthlySeries,
            'monthlyMax' => (float) ($monthlySeries->flatMap(fn (array $month) => [$month['income'], $month['expense']])->max() ?? 0),
            'recentTransactions' => Transaction::with('category')->latest('transaction_date')->latest('id')->limit(5)->get(),
        ]);
    }

    /**
     * Daily, monthly, or yearly income and expense report.
     */
    public function report(Request $request): View
    {
        return view('admin.income-expense.report', $this->buildReport($request));
    }

    /**
     * Printable version of the report.
     */
    public function print(Request $request): View
    {
        return view('admin.income-expense.print', $this->buildReport($request));
    }

    /**
     * Build the report dataset for the requested period.
     *
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['daily', 'monthly', 'yearly'])],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $period = $validated['period'] ?? 'monthly';
        $date = Carbon::parse($validated['date'] ?? Carbon::now()->toDateString());
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : Carbon::now()->startOfMonth();
        $year = (int) ($validated['year'] ?? Carbon::now()->year);

        $rows = collect();
        $entries = null;

        if ($period === 'daily') {
            $entries = Transaction::with('category')
                ->whereDate('transaction_date', $date->toDateString())
                ->orderBy('id')
                ->get();

            $income = round((float) $entries->filter(fn (Transaction $entry) => $entry->type === TransactionType::Income)->sum('amount'), 2);
            $expense = round((float) $entries->filter(fn (Transaction $entry) => $entry->type === TransactionType::Expense)->sum('amount'), 2);
            $periodLabel = $date->format('d M Y');
        } else {
            $rows = $period === 'monthly'
                ? $this->reportRows($month->copy(), $month->copy()->endOfMonth(), 'Y-m-d', 'd M Y')
                : $this->reportRows(Carbon::create($year)->startOfYear(), Carbon::create($year)->endOfYear(), 'Y-m', 'M Y');

            $income = round((float) $rows->sum('income'), 2);
            $expense = round((float) $rows->sum('expense'), 2);
            $periodLabel = $period === 'monthly' ? $month->format('F Y') : (string) $year;
        }

        return [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'rows' => $rows,
            'entries' => $entries,
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'net' => round($income - $expense, 2),
            ],
            'reportMax' => (float) ($rows->flatMap(fn (array $row) => [$row['income'], $row['expense']])->max() ?? 0),
            'selectedDate' => $date->toDateString(),
            'selectedMonth' => $month->format('Y-m'),
            'selectedYear' => $year,
        ];
    }

    /**
     * Income and expense totals grouped per day or per month within a range.
     *
     * @return Collection<int, array{label: string, income: float, expense: float, net: float}>
     */
    private function reportRows(Carbon $start, Carbon $end, string $groupFormat, string $labelFormat): Collection
    {
        return Transaction::selectRaw('transaction_date, type, SUM(amount) as total')
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->groupBy('transaction_date', 'type')
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(fn (Transaction $row) => $row->transaction_date->format($groupFormat))
            ->map(function (Collection $rows) use ($labelFormat) {
                $income = $this->sumForType($rows, TransactionType::Income);
                $expense = $this->sumForType($rows, TransactionType::Expense);

                return [
                    'label' => $rows->first()->transaction_date->format($labelFormat),
                    'income' => $income,
                    'expense' => $expense,
                    'net' => round($income - $expense, 2),
                ];
            })
            ->values();
    }

    /**
     * Per-month income and expense totals for the overview bars, including empty months.
     *
     * @return Collection<int, array{label: string, income: float, expense: float}>
     */
    private function monthlySeries(Carbon $start, Carbon $end): Collection
    {
        $grouped = Transaction::selectRaw('transaction_date, type, SUM(amount) as total')
            ->whereDate('transaction_date', '>=', $start->toDateString())
            ->whereDate('transaction_date', '<=', $end->toDateString())
            ->groupBy('transaction_date', 'type')
            ->get()
            ->groupBy(fn (Transaction $row) => $row->transaction_date->format('Y-m'));

        $months = collect();

        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addMonth()) {
            $rows = $grouped->get($cursor->format('Y-m'), collect());

            $months->push([
                'label' => $cursor->format('M Y'),
                'income' => $this->sumForType($rows, TransactionType::Income),
                'expense' => $this->sumForType($rows, TransactionType::Expense),
            ]);
        }

        return $months;
    }

    /**
     * All-time or date-bounded income, expense, and cash totals.
     *
     * @return array{income: float, expense: float, cash: float}
     */
    private function totalsBetween(?Carbon $from, ?Carbon $to): array
    {
        $rows = Transaction::selectRaw('type, SUM(amount) as total')
            ->when($from !== null, fn ($query) => $query->whereDate('transaction_date', '>=', $from->toDateString()))
            ->when($to !== null, fn ($query) => $query->whereDate('transaction_date', '<=', $to->toDateString()))
            ->groupBy('type')
            ->get();

        $income = round((float) ($rows->firstWhere('type', TransactionType::Income)?->total ?? 0), 2);
        $expense = round((float) ($rows->firstWhere('type', TransactionType::Expense)?->total ?? 0), 2);

        return [
            'income' => $income,
            'expense' => $expense,
            'cash' => round($income - $expense, 2),
        ];
    }

    /**
     * Sum the aggregated totals of one transaction type within a grouped set.
     *
     * @param  Collection<int, Transaction>  $rows
     */
    private function sumForType(Collection $rows, TransactionType $type): float
    {
        return round((float) $rows->filter(fn (Transaction $row) => $row->type === $type)->sum('total'), 2);
    }
}
