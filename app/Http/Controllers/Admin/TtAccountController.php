<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryType;
use App\Enums\TtAccountStatus;
use App\Http\Controllers\Concerns\BuildsDocumentWorkbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTtAccountRequest;
use App\Http\Requests\Admin\UpdateTtAccountRequest;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\TtAccount;
use App\Models\TtAccountEntry;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TtAccountController extends Controller
{
    use BuildsDocumentWorkbook;

    /**
     * List TT accounts with optional search and customer/status filters.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $customerId = $request->integer('customer');
        $status = $request->string('status')->toString();

        $ttAccounts = TtAccount::with(['customer', 'currency'])
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($customerId > 0, fn ($query) => $query->where('customer_id', $customerId))
            ->when(TtAccountStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tt-accounts.index', [
            'ttAccounts' => $ttAccounts,
            'customers' => User::customers()->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'customerId' => $customerId,
            'status' => $status,
        ]);
    }

    /**
     * Show the form to create a TT account.
     */
    public function create(Request $request): View
    {
        return view('admin.tt-accounts.create', [
            'preselectedCustomerId' => $request->integer('customer'),
            ...$this->formData(),
        ]);
    }

    /**
     * Store a TT account.
     */
    public function store(StoreTtAccountRequest $request): RedirectResponse
    {
        $ttAccount = TtAccount::create($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'TT account created successfully.');
    }

    /**
     * Show a TT account statement with its running balance.
     */
    public function show(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.show', [
            ...$this->statementData($ttAccount),
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Show the form to edit a TT account.
     */
    public function edit(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.edit', [
            'ttAccount' => $ttAccount,
            ...$this->formData(),
        ]);
    }

    /**
     * Update a TT account.
     */
    public function update(UpdateTtAccountRequest $request, TtAccount $ttAccount): RedirectResponse
    {
        $ttAccount->update($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'TT account updated successfully.');
    }

    /**
     * Delete a TT account and its entries.
     */
    public function destroy(TtAccount $ttAccount): RedirectResponse
    {
        $ttAccount->delete();

        return redirect()
            ->route('admin.tt-accounts.index')
            ->with('success', 'TT account deleted successfully.');
    }

    /**
     * Show the printable account statement.
     */
    public function print(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.print', $this->statementData($ttAccount));
    }

    /**
     * Download the TT account statement as a PDF document.
     */
    public function pdf(TtAccount $ttAccount): Response
    {
        $pdf = Pdf::loadView('admin.tt-accounts.pdf', $this->statementData($ttAccount))->setPaper('a4');

        return $pdf->download("tt-account-{$ttAccount->id}.pdf");
    }

    /**
     * Download the TT account statement as a styled Excel workbook mirroring the printed document.
     */
    public function excel(TtAccount $ttAccount): StreamedResponse
    {
        $data = $this->statementData($ttAccount);

        [$spreadsheet, $sheet, $row] = $this->startDocumentWorkbook(
            'Statement',
            $ttAccount->title,
            ['A' => 14, 'B' => 38, 'C' => 15, 'D' => 15, 'E' => 16, 'F' => 22],
        );

        $currency = $ttAccount->currency;
        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');

        // ---- Account + statement meta -------------------------------------
        $this->documentSectionLabel($sheet, "A{$row}", 'ACCOUNT');
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Currency', $currency->code.' ('.$currency->symbol.')');
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", $ttAccount->title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($row)->setRowHeight(20);
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Status', $ttAccount->status->label());
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", $ttAccount->customer->name);
        $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
        $this->documentMetaPair($sheet, $row, 'E', 'F', 'Printed', now()->format('d M Y'));
        $row += 2;

        // ---- Statement -------------------------------------------------------
        $this->documentTableHeader($sheet, $row, ['Date', 'Description', 'Received', 'Paid', 'Balance', 'Remarks']);
        $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $firstEntryRow = $row;

        if ($ttAccount->opening_balance !== null) {
            $sheet->setCellValue("A{$row}", '—');
            $sheet->setCellValue("B{$row}", 'Opening balance');
            $sheet->setCellValue("E{$row}", (float) $ttAccount->opening_balance);
            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('94A3B8');
            $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle("E{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $row++;
        }

        $striped = 0;

        foreach ($data['entries'] as $entry) {
            $source = null;

            if ($entry->source_amount !== null) {
                $source = trim(($entry->sourceCurrency?->code ?? '').' '.number_format((float) $entry->source_amount, 2))
                    .($entry->source_rate !== null ? ' @ '.(float) $entry->source_rate : '');
            }

            $description = $entry->description.($source ? "\n".$source : '');

            $sheet->setCellValue("A{$row}", $entry->entry_date?->format('d M Y') ?? '—');
            $sheet->setCellValue("B{$row}", $description);
            $sheet->setCellValue("C{$row}", $entry->type === EntryType::Received ? (float) $entry->amount : null);
            $sheet->setCellValue("D{$row}", $entry->type === EntryType::Paid ? (float) $entry->amount : null);
            $sheet->setCellValue("E{$row}", $entry->running_balance);
            $sheet->setCellValue("F{$row}", $entry->remarks);

            $sheet->getStyle("A{$row}")->getFont()->getColor()->setRGB('64748B');
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("E{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle("F{$row}")->getFont()->setSize(9)->getColor()->setRGB('64748B');
            $sheet->getStyle("F{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($description, 38, 15.0, 20.0));

            if ($striped % 2 === 1) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $striped++;
            $row++;
        }

        if ($row === $firstEntryRow) {
            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", 'No entries');
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $lastEntryRow = $row - 1;
        $sheet->getStyle("C{$firstEntryRow}:E{$lastEntryRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("C{$firstEntryRow}:E{$lastEntryRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$firstEntryRow}:F{$lastEntryRow}")->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // ---- Totals -----------------------------------------------------------
        $sheet->setCellValue("B{$row}", 'TOTALS');
        $sheet->setCellValue("C{$row}", $data['totalReceived']);
        $sheet->setCellValue("D{$row}", $data['totalPaid']);
        $sheet->setCellValue("E{$row}", $data['closingBalance']);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("C{$row}:E{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
        $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row += 2;

        // ---- Closing balance ---------------------------------------------------
        $sheet->mergeCells("C{$row}:D{$row}");
        $sheet->setCellValue("C{$row}", 'CLOSING BALANCE');
        $sheet->getStyle("C{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("C{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->mergeCells("E{$row}:F{$row}");
        $sheet->setCellValue("E{$row}", $data['closingBalance']);
        $sheet->getStyle("E{$row}")->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('0F172A');
        $sheet->getStyle("E{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode('"'.$currency->symbol.'"#,##0.00');
        $sheet->getStyle("C{$row}:F{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row++;

        $sheet->mergeCells("C{$row}:F{$row}");
        $sheet->setCellValue("C{$row}", 'Balance in '.$currency->name.' ('.$currency->code.')');
        $sheet->getStyle("C{$row}")->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $row = $this->documentSignature($sheet, $row, 'E', 'F', null, null, $companyName);

        $this->finishDocumentWorkbook($sheet, $row, 'F');

        return $this->streamWorkbook($spreadsheet, "tt-account-{$ttAccount->id}.xlsx");
    }

    /**
     * Shared statement data with the running balance computed in insertion
     * (id) order — many rows carry no date, so the balance follows the order
     * entries were recorded rather than the display-only entry date.
     *
     * @return array<string, mixed>
     */
    private function statementData(TtAccount $ttAccount): array
    {
        $ttAccount->load(['customer', 'currency', 'entries.sourceCurrency']);

        $running = (float) ($ttAccount->opening_balance ?? 0);

        $entries = $ttAccount->entries->map(function (TtAccountEntry $entry) use (&$running): TtAccountEntry {
            $signed = $entry->type === EntryType::Received ? (float) $entry->amount : -(float) $entry->amount;
            $entry->running_balance = $running = round($running + $signed, 2);

            return $entry;
        });

        return [
            'ttAccount' => $ttAccount,
            'entries' => $entries,
            'totalReceived' => (float) $ttAccount->entries->where('type', EntryType::Received)->sum('amount'),
            'totalPaid' => (float) $ttAccount->entries->where('type', EntryType::Paid)->sum('amount'),
            'closingBalance' => $running,
        ];
    }

    /**
     * Shared dropdown data for the TT account form.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'currencies' => Currency::active()->orderBy('code')->get(),
            'customers' => User::customers()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
