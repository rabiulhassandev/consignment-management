<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsDocumentWorkbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    use BuildsDocumentWorkbook;

    /**
     * List invoices with optional search by invoice number or bill-to name.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $invoices = Invoice::with('currency')
            ->withCount('items')
            ->withSum('items', 'amount')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('bill_to', 'like', "%{$search}%"),
            ))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create an invoice.
     */
    public function create(): View
    {
        return view('admin.invoices.create', [
            'suggestedNumber' => $this->suggestInvoiceNumber(),
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Store an invoice and its line items.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated) {
            $invoice = Invoice::create(Arr::except($validated, 'items'));

            $invoice->items()->createMany(
                collect($validated['items'])->values()->map(
                    fn (array $item, int $index): array => [...Arr::except($item, 'id'), 'sort_order' => $index],
                ),
            );

            return $invoice;
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /**
     * Show an invoice with its line items.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load(['currency', 'items']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->items->sum('amount'),
        ]);
    }

    /**
     * Show the form to edit an invoice.
     */
    public function edit(Invoice $invoice): View
    {
        $invoice->load('items');

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Update an invoice and sync its line items.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $invoice) {
            $invoice->update(Arr::except($validated, 'items'));

            $items = collect($validated['items'])->values();

            $invoice->items()
                ->whereNotIn('id', $items->pluck('id')->filter())
                ->delete();

            foreach ($items as $index => $item) {
                $invoice->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [...Arr::except($item, 'id'), 'sort_order' => $index],
                );
            }
        });

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /**
     * Delete an invoice and its line items.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Show the printable invoice document.
     */
    public function print(Invoice $invoice): View
    {
        $invoice->load(['currency', 'items']);

        return view('admin.invoices.print', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->items->sum('amount'),
        ]);
    }

    /**
     * Download the invoice as a PDF document.
     */
    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['currency', 'items']);

        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->items->sum('amount'),
        ])->setPaper('a4');

        return $pdf->download("invoice-{$invoice->invoice_no}.pdf");
    }

    /**
     * Download the invoice as a styled Excel workbook mirroring the printed document.
     */
    public function excel(Invoice $invoice): StreamedResponse
    {
        $invoice->load(['currency', 'items']);

        [$spreadsheet, $sheet, $row] = $this->startDocumentWorkbook(
            'Invoice',
            'Invoice '.$invoice->invoice_no,
            ['A' => 44, 'B' => 17, 'C' => 17, 'D' => 22],
        );

        $currency = $invoice->currency;
        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');

        // ---- Billed to + invoice meta -----------------------------------
        $this->documentSectionLabel($sheet, "A{$row}", 'BILLED TO');
        $this->documentMetaPair($sheet, $row, 'C', 'D', 'Invoice No', $invoice->invoice_no);
        $row++;

        $sheet->setCellValue("A{$row}", $invoice->bill_to);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($row)->setRowHeight(20);
        $this->documentMetaPair($sheet, $row, 'C', 'D', 'Issue Date', $invoice->invoice_date->format('d M Y'));
        $row++;

        $this->documentMetaPair($sheet, $row, 'C', 'D', 'Currency', $currency->code);

        if ($invoice->bill_to_address) {
            $sheet->setCellValue("A{$row}", $invoice->bill_to_address);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($invoice->bill_to_address, 48));
        }

        $row += 2;

        // ---- Items -------------------------------------------------------
        $this->documentTableHeader($sheet, $row, ['Description', 'Qty / Weight', 'Rate', 'Amount ('.$currency->code.')']);
        $sheet->getStyle("B{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $firstItemRow = $row;

        foreach ($invoice->items as $index => $item) {
            $sheet->setCellValue("A{$row}", $item->description);
            $sheet->setCellValue("B{$row}", $item->quantity !== null ? (float) $item->quantity : '—');
            $sheet->setCellValue("C{$row}", $item->rate !== null ? (float) $item->rate : '—');
            $sheet->setCellValue("D{$row}", (float) $item->amount);

            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}:C{$row}")->getFont()->getColor()->setRGB('64748B');
            $sheet->getStyle("D{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($item->description, 44, 15.0, 20.0));

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:D{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        $lastItemRow = $row - 1;

        if ($lastItemRow >= $firstItemRow) {
            $sheet->getStyle("B{$firstItemRow}:D{$lastItemRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("B{$firstItemRow}:C{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$firstItemRow}:D{$lastItemRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // ---- Total -------------------------------------------------------
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", (float) $invoice->items->sum('amount'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("A{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('0F172A');
        $sheet->getStyle("D{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('"'.$currency->symbol.'"#,##0.00');
        $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'Amount in '.$currency->name.' ('.$currency->code.') only');
        $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        if ($paymentTerms = Setting::get('invoice_payment_terms')) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $paymentTerms);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('1E293B');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        $row++;

        // ---- Terms & conditions ------------------------------------------
        if ($terms = Setting::get('invoice_terms')) {
            $this->documentSectionLabel($sheet, "A{$row}", 'TERMS & CONDITIONS');
            $row++;

            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $terms);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($terms, 110));
            $row += 2;
        }

        // ---- Payment details ---------------------------------------------
        $bankRows = array_filter([
            'Bank' => Setting::get('bank_name'),
            'Account Name' => Setting::get('bank_account_name'),
            'Account No.' => Setting::get('bank_account_number'),
            'Branch' => Setting::get('bank_branch'),
            'SWIFT / BIC' => Setting::get('bank_swift_code'),
            'Routing No.' => Setting::get('bank_routing_number'),
        ]);

        if ($bankRows !== []) {
            $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $this->documentSectionLabel($sheet, "A{$row}", 'PAYMENT DETAILS');
            $row++;

            foreach ($bankRows as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
                $sheet->mergeCells("B{$row}:D{$row}");
                $sheet->setCellValue("B{$row}", $value);
                $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
                $row++;
            }

            $row++;
        }

        // ---- Signature + footer -------------------------------------------
        $row = $this->documentSignature(
            $sheet,
            $row,
            'C',
            'D',
            Setting::get('invoice_signatory_name'),
            Setting::get('invoice_signatory_designation'),
            $companyName,
        );

        $this->finishDocumentWorkbook($sheet, $row, 'D');

        return $this->streamWorkbook($spreadsheet, "invoice-{$invoice->invoice_no}.xlsx");
    }

    /**
     * Suggest the next invoice number (editable by the admin).
     */
    private function suggestInvoiceNumber(): string
    {
        $next = (Invoice::max('id') ?? 0) + 1;

        do {
            $number = 'INV-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Invoice::where('invoice_no', $number)->exists());

        return $number;
    }
}
