<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
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

        $writer = new Xlsx($this->buildInvoiceWorkbook($invoice));
        $filename = "invoice-{$invoice->invoice_no}.xlsx";

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, must-revalidate',
        ]);
    }

    /**
     * Build the full invoice workbook: letterhead, parties, items, totals, terms, and footer.
     */
    private function buildInvoiceWorkbook(Invoice $invoice): Spreadsheet
    {
        $slate = '1E293B';
        $ink = '0F172A';
        $soft = '64748B';
        $muted = '94A3B8';
        $rule = 'CBD5E1';
        $wash = 'F8FAFC';

        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');
        $tagline = Setting::get('company_tagline');
        $registrationNo = Setting::get('company_registration_no');
        $logo = Setting::get('site_logo');
        $website = Setting::get('company_website');
        $siteEmail = Setting::get('site_email');
        $chinaAddress = Setting::get('china_office_address');
        $chinaContact = Setting::get('china_office_contact');
        $dhakaAddress = Setting::get('dhaka_office_address');
        $dhakaContact = Setting::get('dhaka_office_contact');

        $bankName = Setting::get('bank_name');
        $bankAccountName = Setting::get('bank_account_name');
        $bankAccountNumber = Setting::get('bank_account_number');
        $bankBranch = Setting::get('bank_branch');
        $bankSwiftCode = Setting::get('bank_swift_code');
        $bankRoutingNumber = Setting::get('bank_routing_number');
        $paymentTerms = Setting::get('invoice_payment_terms');
        $terms = Setting::get('invoice_terms');
        $signatoryName = Setting::get('invoice_signatory_name');
        $signatoryDesignation = Setting::get('invoice_signatory_designation');
        $footerNote = Setting::get('invoice_footer_note');

        $currency = $invoice->currency;
        $totalAmount = (float) $invoice->items->sum('amount');
        $moneyFormat = '#,##0.00';
        $totalFormat = '"'.$currency->symbol.'"#,##0.00';

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator($companyName)
            ->setTitle('Invoice '.$invoice->invoice_no)
            ->setSubject('Invoice '.$invoice->invoice_no)
            ->setDescription('Invoice for '.$invoice->bill_to);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoice');
        $sheet->setShowGridlines(false);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $sheet->getColumnDimension('A')->setWidth(44);
        $sheet->getColumnDimension('B')->setWidth(17);
        $sheet->getColumnDimension('C')->setWidth(17);
        $sheet->getColumnDimension('D')->setWidth(22);

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);

        // ---- Letterhead -------------------------------------------------
        $sheet->getRowDimension(1)->setRowHeight(50);
        $sheet->mergeCells('C1:D2');
        $sheet->setCellValue('C1', 'INVOICE');
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(22)->getColor()->setRGB($slate);
        $sheet->getStyle('C1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($logo) {
            $logoPath = Storage::disk('public')->path($logo);

            if (is_file($logoPath)) {
                $drawing = new Drawing;
                $drawing->setPath($logoPath);
                $drawing->setHeight(44);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(4);
                $drawing->setWorksheet($sheet);
            }
        }

        if ($tagline) {
            $sheet->mergeCells('A2:B2');
            $sheet->setCellValue('A2', $tagline);
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB($muted);
            $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(18);
        }

        $sheet->mergeCells('A3:D3');
        $sheet->setCellValue('A3', mb_strtoupper($companyName));
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0A0A0A');
        $sheet->getRowDimension(3)->setRowHeight(22);

        $row = 4;

        if ($registrationNo) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", 'Reg. No. '.$registrationNo);
            $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB($muted);
            $row++;
        }

        $sheet->getStyle('A'.($row - 1).':D'.($row - 1))->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($slate);

        $row++;

        // ---- Billed to + invoice meta -----------------------------------
        $billedToRow = $row;
        $sheet->setCellValue("A{$row}", 'BILLED TO');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB($muted);
        $this->setMetaPair($sheet, $row, 'Invoice No', $invoice->invoice_no, $soft, $ink);
        $row++;

        $sheet->setCellValue("A{$row}", $invoice->bill_to);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB($ink);
        $sheet->getRowDimension($row)->setRowHeight(20);
        $this->setMetaPair($sheet, $row, 'Issue Date', $invoice->invoice_date->format('d M Y'), $soft, $ink);
        $row++;

        $this->setMetaPair($sheet, $row, 'Currency', $currency->code, $soft, $ink);

        if ($invoice->bill_to_address) {
            $sheet->setCellValue("A{$row}", $invoice->bill_to_address);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB($soft);
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($invoice->bill_to_address, 48));
        }

        $sheet->getStyle("C{$billedToRow}:C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("D{$billedToRow}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row += 2;

        // ---- Items -------------------------------------------------------
        $headerRow = $row;
        foreach (['Description', 'Qty / Weight', 'Rate', 'Amount ('.$currency->code.')'] as $index => $label) {
            $sheet->setCellValue([$index + 1, $headerRow], $label);
        }
        $sheet->getStyle("A{$headerRow}:D{$headerRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$headerRow}:D{$headerRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($slate);
        $sheet->getStyle("A{$headerRow}:D{$headerRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("B{$headerRow}:D{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);
        $row++;

        $firstItemRow = $row;
        foreach ($invoice->items as $index => $item) {
            $sheet->setCellValue("A{$row}", $item->description);
            $sheet->setCellValue("B{$row}", $item->quantity !== null ? (float) $item->quantity : '—');
            $sheet->setCellValue("C{$row}", $item->rate !== null ? (float) $item->rate : '—');
            $sheet->setCellValue("D{$row}", (float) $item->amount);

            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}:C{$row}")->getFont()->getColor()->setRGB($soft);
            $sheet->getStyle("D{$row}")->getFont()->setBold(true)->getColor()->setRGB($ink);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($item->description, 44, 15.0, 20.0));

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:D{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($wash);
            }

            $row++;
        }

        $lastItemRow = $row - 1;

        if ($lastItemRow >= $firstItemRow) {
            $sheet->getStyle("B{$firstItemRow}:D{$lastItemRow}")->getNumberFormat()->setFormatCode($moneyFormat);
            $sheet->getStyle("B{$firstItemRow}:C{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$firstItemRow}:D{$lastItemRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($rule);
        }

        // ---- Total -------------------------------------------------------
        $totalRow = $row;
        $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("D{$totalRow}", $totalAmount);
        $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($soft);
        $sheet->getStyle("A{$totalRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$totalRow}")->getFont()->setBold(true)->setSize(15)->getColor()->setRGB($ink);
        $sheet->getStyle("D{$totalRow}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("D{$totalRow}")->getNumberFormat()->setFormatCode($totalFormat);
        $sheet->getStyle("A{$totalRow}:D{$totalRow}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($slate);
        $sheet->getRowDimension($totalRow)->setRowHeight(28);
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'Amount in '.$currency->name.' ('.$currency->code.') only');
        $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB($muted);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        if ($paymentTerms) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $paymentTerms);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB($slate);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        $row++;

        // ---- Terms & conditions ------------------------------------------
        if ($terms) {
            $sheet->setCellValue("A{$row}", 'TERMS & CONDITIONS');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB($muted);
            $row++;

            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $terms);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB($soft);
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($terms, 110));
            $row += 2;
        }

        // ---- Payment details ---------------------------------------------
        $bankRows = array_filter([
            'Bank' => $bankName,
            'Account Name' => $bankAccountName,
            'Account No.' => $bankAccountNumber,
            'Branch' => $bankBranch,
            'SWIFT / BIC' => $bankSwiftCode,
            'Routing No.' => $bankRoutingNumber,
        ]);

        if ($bankRows !== []) {
            $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($rule);
            $sheet->setCellValue("A{$row}", 'PAYMENT DETAILS');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->getColor()->setRGB($muted);
            $row++;

            foreach ($bankRows as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB($soft);
                $sheet->mergeCells("B{$row}:D{$row}");
                $sheet->setCellValue("B{$row}", $value);
                $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($slate);
                $row++;
            }

            $row++;
        }

        // ---- Signature ----------------------------------------------------
        $row += 2;
        $sheet->getStyle("C{$row}:D{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($slate);

        $sheet->mergeCells("C{$row}:D{$row}");
        $sheet->setCellValue("C{$row}", $signatoryName ?: 'Authorized Signature');
        $sheet->getStyle("C{$row}")->getFont()->setBold((bool) $signatoryName)->setSize(11)->getColor()->setRGB($slate);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        if ($signatoryName && $signatoryDesignation) {
            $sheet->mergeCells("C{$row}:D{$row}");
            $sheet->setCellValue("C{$row}", $signatoryDesignation);
            $sheet->getStyle("C{$row}")->getFont()->setSize(9)->getColor()->setRGB($soft);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sheet->mergeCells("C{$row}:D{$row}");
        $sheet->setCellValue("C{$row}", 'For '.$companyName);
        $sheet->getStyle("C{$row}")->getFont()->setSize(9)->getColor()->setRGB($muted);
        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight('For '.$companyName, 38));
        $row += 2;

        // ---- Footer note ---------------------------------------------------
        if ($footerNote) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $footerNote);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB($muted);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($footerNote, 110));
            $row += 2;
        }

        // ---- Office footer --------------------------------------------------
        $officeLines = array_filter([
            $chinaAddress ? 'China Office: '.$chinaAddress.($chinaContact ? ' · '.$chinaContact : '') : null,
            $dhakaAddress ? 'Dhaka Office: '.$dhakaAddress.($dhakaContact ? ' · '.$dhakaContact : '') : null,
            implode('    ', array_filter([$website, $siteEmail])) ?: null,
        ]);

        if ($officeLines !== []) {
            $sheet->getStyle("A{$row}:D{$row}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($rule);

            foreach ($officeLines as $line) {
                $sheet->mergeCells("A{$row}:D{$row}");
                $sheet->setCellValue("A{$row}", $line);
                $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB($soft);
                $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($line, 105, 12.0));
                $row++;
            }
        }

        // Brand bar closing the sheet, matching the printed footer.
        $sheet->getRowDimension($row)->setRowHeight(7);
        $brandBar = $sheet->getStyle("A{$row}:D{$row}")->getFill();
        $brandBar->setFillType(Fill::FILL_GRADIENT_LINEAR);
        $brandBar->setRotation(0);
        $brandBar->getStartColor()->setRGB('8DC63F');
        $brandBar->getEndColor()->setRGB('27AAE1');

        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    /**
     * Write a right-aligned label/value pair into the invoice meta column.
     */
    private function setMetaPair(Worksheet $sheet, int $row, string $label, string $value, string $labelColor, string $valueColor): void
    {
        $sheet->setCellValue("C{$row}", $label);
        $sheet->getStyle("C{$row}")->getFont()->setSize(10)->getColor()->setRGB($labelColor);

        $sheet->setCellValueExplicit("D{$row}", $value, DataType::TYPE_STRING);
        $sheet->getStyle("D{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB($valueColor);
    }

    /**
     * Estimate a row height that fits wrapped text, since Excel cannot auto-fit merged cells.
     */
    private function wrappedRowHeight(?string $text, int $charactersPerLine, float $lineHeight = 14.0, float $minimum = 16.0): float
    {
        if (! $text) {
            return $minimum;
        }

        $lines = 0;

        foreach (preg_split('/\R/', $text) ?: [] as $paragraph) {
            $lines += max(1, (int) ceil(mb_strlen($paragraph) / $charactersPerLine));
        }

        return max($minimum, $lines * $lineHeight);
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
