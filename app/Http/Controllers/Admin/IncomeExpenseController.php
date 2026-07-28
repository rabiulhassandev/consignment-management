<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Concerns\BuildsDocumentWorkbook;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomeExpenseController extends Controller
{
    use BuildsDocumentWorkbook;

    /**
     * Selectable report periods and their display names.
     */
    private const PERIOD_NAMES = [
        'daily' => 'Daily',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'range' => 'Custom Range',
    ];

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
     * Daily, monthly, yearly, or custom date range income and expense report.
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
     * Download the report as a bank-statement style PDF document.
     */
    public function pdf(Request $request): Response
    {
        $data = $this->buildReport($request);

        $pdf = Pdf::loadView('admin.income-expense.pdf', $data)->setPaper('a4');

        return $pdf->download($this->statementFilename($data['periodLabel'], 'pdf'));
    }

    /**
     * Download the report as a bank-statement style Excel workbook.
     */
    public function excel(Request $request): StreamedResponse
    {
        $data = $this->buildReport($request);

        [$spreadsheet, $sheet, $row] = $this->startDocumentWorkbook(
            'Statement',
            'Income & Expense Statement — '.$data['periodLabel'],
            ['A' => 14, 'B' => 36, 'C' => 20, 'D' => 15, 'E' => 15, 'F' => 17],
        );

        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');

        // ---- Statement meta ------------------------------------------------
        $this->documentSectionLabel($sheet, "A{$row}", 'STATEMENT PERIOD');
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Report Type', $data['periodName']);
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", $data['periodLabel']);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($row)->setRowHeight(20);
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Statement Date', now()->format('d M Y'));
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", $data['rangeStart']->format('d M Y').' to '.$data['rangeEnd']->format('d M Y'));
        $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Entries', (string) $data['statement']->count());
        $row += 2;

        // ---- Statement lines -------------------------------------------------
        $this->documentTableHeader($sheet, $row, ['Date', 'Particulars', 'Category', 'Income', 'Expense', 'Balance']);
        $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $firstLineRow = $row;

        $sheet->setCellValue("A{$row}", $data['rangeStart']->format('d M Y'));
        $sheet->setCellValue("B{$row}", 'Opening balance');
        $sheet->setCellValue("F{$row}", $data['openingBalance']);
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('64748B');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
        $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
        $row++;

        foreach ($data['statement'] as $index => $line) {
            $sheet->setCellValue("A{$row}", $line['date']);
            $sheet->setCellValue("B{$row}", $line['particulars']);
            $sheet->setCellValue("C{$row}", $line['category']);
            $sheet->setCellValue("D{$row}", $line['income'] > 0 ? $line['income'] : null);
            $sheet->setCellValue("E{$row}", $line['expense'] > 0 ? $line['expense'] : null);
            $sheet->setCellValue("F{$row}", $line['balance']);

            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('64748B');
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$row}")->getFont()->setSize(9)->getColor()->setRGB('64748B');
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($line['particulars'], 36, 15.0, 20.0));

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if ($data['statement']->isEmpty()) {
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'No entries in this period');
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $lastLineRow = $row - 1;
        $sheet->getStyle("D{$firstLineRow}:F{$lastLineRow}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00');
        $sheet->getStyle("D{$firstLineRow}:F{$lastLineRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$firstLineRow}:F{$lastLineRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // ---- Totals ----------------------------------------------------------
        $sheet->setCellValue("B{$row}", 'TOTALS');
        $sheet->setCellValue("D{$row}", $data['totals']['income']);
        $sheet->setCellValue("E{$row}", $data['totals']['expense']);
        $sheet->setCellValue("F{$row}", $data['closingBalance']);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("D{$row}:F{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
        $sheet->getStyle("D{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00');
        $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row += 2;

        // ---- Summary ---------------------------------------------------------
        $summary = [
            ['Opening balance', $data['openingBalance']],
            ['Total income', $data['totals']['income']],
            ['Total expense', $data['totals']['expense']],
            ['Net movement', $data['totals']['net']],
        ];

        foreach ($summary as [$label, $value]) {
            $sheet->mergeCells("D{$row}:E{$row}");
            $sheet->setCellValue("D{$row}", $label);
            $sheet->setCellValue("F{$row}", $value);
            $sheet->getStyle("D{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$row}")->getFont()->setSize(10)->getColor()->setRGB('1E293B');
            $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00');
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->setCellValue("D{$row}", 'CLOSING BALANCE');
        $sheet->setCellValue("F{$row}", $data['closingBalance']);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("D{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('0F172A');
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('#,##0.00;-#,##0.00');
        $sheet->getStyle("F{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$row}:F{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row++;

        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'This is a system generated statement.');
        $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $row++;

        $row = $this->documentSignature($sheet, $row, 'E', 'F', null, null, $companyName);

        $this->finishDocumentWorkbook($sheet, $row, 'F');

        return $this->streamWorkbook($spreadsheet, $this->statementFilename($data['periodLabel'], 'xlsx'));
    }

    /**
     * Build the report dataset for the requested period.
     *
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(self::PERIOD_NAMES))],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $period = $validated['period'] ?? 'monthly';
        $date = Carbon::parse($validated['date'] ?? Carbon::now()->toDateString())->startOfDay();
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : Carbon::now()->startOfMonth();
        $year = (int) ($validated['year'] ?? Carbon::now()->year);
        $from = Carbon::parse($validated['date_from'] ?? Carbon::now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($validated['date_to'] ?? Carbon::now()->toDateString())->startOfDay();

        if ($to->lessThan($from)) {
            $to = $from->copy();
        }

        [$start, $end] = match ($period) {
            'daily' => [$date->copy(), $date->copy()],
            'yearly' => [Carbon::create($year)->startOfYear(), Carbon::create($year)->endOfYear()],
            'range' => [$from->copy(), $to->copy()],
            default => [$month->copy(), $month->copy()->endOfMonth()],
        };

        $openingBalance = $this->balanceBefore($start);
        $rows = collect();
        $entries = null;

        if ($period === 'daily' || $period === 'range') {
            $entries = Transaction::with('category')
                ->whereDate('transaction_date', '>=', $start->toDateString())
                ->whereDate('transaction_date', '<=', $end->toDateString())
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $statement = $this->statementFromEntries($entries, $openingBalance);
        } else {
            $rows = $period === 'monthly'
                ? $this->reportRows($start, $end, 'Y-m-d', 'd M Y')
                : $this->reportRows($start, $end, 'Y-m', 'M Y');

            $statement = $this->statementFromRows($rows, $openingBalance, $period === 'monthly' ? 'Daily total' : 'Monthly total');
        }

        $income = round((float) $statement->sum('income'), 2);
        $expense = round((float) $statement->sum('expense'), 2);

        return [
            'period' => $period,
            'periodName' => self::PERIOD_NAMES[$period],
            'periodLabel' => match ($period) {
                'daily' => $date->format('d M Y'),
                'yearly' => (string) $year,
                'range' => $from->format('d M Y').' — '.$to->format('d M Y'),
                default => $month->format('F Y'),
            },
            'rangeStart' => $start,
            'rangeEnd' => $end,
            'rows' => $rows,
            'entries' => $entries,
            'statement' => $statement,
            'totals' => [
                'income' => $income,
                'expense' => $expense,
                'net' => round($income - $expense, 2),
            ],
            'openingBalance' => $openingBalance,
            'closingBalance' => round($openingBalance + $income - $expense, 2),
            'reportMax' => (float) ($rows->flatMap(fn (array $row) => [$row['income'], $row['expense']])->max() ?? 0),
            'selectedDate' => $date->toDateString(),
            'selectedMonth' => $month->format('Y-m'),
            'selectedYear' => $year,
            'selectedFrom' => $from->toDateString(),
            'selectedTo' => $to->toDateString(),
        ];
    }

    /**
     * Statement lines for individual transactions, carrying a running balance.
     *
     * @param  Collection<int, Transaction>  $entries
     * @return Collection<int, array{date: string, particulars: string, category: string, income: float, expense: float, net: float, balance: float}>
     */
    private function statementFromEntries(Collection $entries, float $openingBalance): Collection
    {
        $balance = $openingBalance;

        return $entries->map(function (Transaction $entry) use (&$balance): array {
            $income = $entry->type === TransactionType::Income ? round((float) $entry->amount, 2) : 0.0;
            $expense = $entry->type === TransactionType::Expense ? round((float) $entry->amount, 2) : 0.0;
            $balance = round($balance + $income - $expense, 2);

            return [
                'date' => $entry->transaction_date->format('d M Y'),
                'particulars' => $entry->description ?: $entry->category->name,
                'category' => $entry->category->name,
                'income' => $income,
                'expense' => $expense,
                'net' => round($income - $expense, 2),
                'balance' => $balance,
            ];
        })->values();
    }

    /**
     * Statement lines for grouped period totals, carrying a running balance.
     *
     * @param  Collection<int, array{label: string, count: int, income: float, expense: float, net: float}>  $rows
     * @return Collection<int, array{date: string, particulars: string, category: string, income: float, expense: float, net: float, balance: float}>
     */
    private function statementFromRows(Collection $rows, float $openingBalance, string $particulars): Collection
    {
        $balance = $openingBalance;

        return $rows->map(function (array $row) use (&$balance, $particulars): array {
            $balance = round($balance + $row['income'] - $row['expense'], 2);

            return [
                'date' => $row['label'],
                'particulars' => $particulars,
                'category' => $row['count'].' '.Str::plural('entry', $row['count']),
                'income' => $row['income'],
                'expense' => $row['expense'],
                'net' => $row['net'],
                'balance' => $balance,
            ];
        })->values();
    }

    /**
     * Income and expense totals grouped per day or per month within a range.
     *
     * @return Collection<int, array{label: string, count: int, income: float, expense: float, net: float}>
     */
    private function reportRows(Carbon $start, Carbon $end, string $groupFormat, string $labelFormat): Collection
    {
        return Transaction::selectRaw('transaction_date, type, SUM(amount) as total, COUNT(*) as entry_count')
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
                    'count' => (int) $rows->sum('entry_count'),
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
     * Net cash brought forward from every transaction before the period starts.
     */
    private function balanceBefore(Carbon $start): float
    {
        $rows = Transaction::selectRaw('type, SUM(amount) as total')
            ->whereDate('transaction_date', '<', $start->toDateString())
            ->groupBy('type')
            ->get();

        $income = round((float) ($rows->firstWhere('type', TransactionType::Income)?->total ?? 0), 2);
        $expense = round((float) ($rows->firstWhere('type', TransactionType::Expense)?->total ?? 0), 2);

        return round($income - $expense, 2);
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

    /**
     * Download filename for the generated statement.
     */
    private function statementFilename(string $periodLabel, string $extension): string
    {
        return 'income-expense-statement-'.Str::slug($periodLabel).'.'.$extension;
    }
}
