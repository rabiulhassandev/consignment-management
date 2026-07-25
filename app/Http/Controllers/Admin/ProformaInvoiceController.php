<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsDocumentWorkbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProformaInvoiceRequest;
use App\Http\Requests\Admin\UpdateProformaInvoiceRequest;
use App\Models\Currency;
use App\Models\ProformaInvoice;
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

class ProformaInvoiceController extends Controller
{
    use BuildsDocumentWorkbook;

    /**
     * List proforma invoices with optional search by invoice number or buyer.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $proformaInvoices = ProformaInvoice::with('currency')
            ->withCount('items')
            ->withSum('items', 'amount')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%"),
            ))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.proforma-invoices.index', [
            'proformaInvoices' => $proformaInvoices,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create a proforma invoice.
     */
    public function create(): View
    {
        return view('admin.proforma-invoices.create', [
            'suggestedNumber' => $this->suggestInvoiceNumber(),
            'defaults' => $this->documentDefaults(),
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Store a proforma invoice and its line items.
     */
    public function store(StoreProformaInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $proformaInvoice = DB::transaction(function () use ($validated) {
            $proformaInvoice = ProformaInvoice::create(Arr::except($validated, 'items'));

            $proformaInvoice->items()->createMany(
                collect($validated['items'])->values()->map(
                    fn (array $item, int $index): array => [...Arr::except($item, 'id'), 'sort_order' => $index],
                ),
            );

            return $proformaInvoice;
        });

        return redirect()
            ->route('admin.proforma-invoices.show', $proformaInvoice)
            ->with('success', 'Proforma invoice created successfully.');
    }

    /**
     * Show a proforma invoice with its line items.
     */
    public function show(ProformaInvoice $proformaInvoice): View
    {
        return view('admin.proforma-invoices.show', $this->invoiceData($proformaInvoice));
    }

    /**
     * Show the form to edit a proforma invoice.
     */
    public function edit(ProformaInvoice $proformaInvoice): View
    {
        $proformaInvoice->load('items');

        return view('admin.proforma-invoices.edit', [
            'proformaInvoice' => $proformaInvoice,
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Update a proforma invoice and sync its line items.
     */
    public function update(UpdateProformaInvoiceRequest $request, ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $proformaInvoice) {
            $proformaInvoice->update(Arr::except($validated, 'items'));

            $items = collect($validated['items'])->values();

            $proformaInvoice->items()
                ->whereNotIn('id', $items->pluck('id')->filter())
                ->delete();

            foreach ($items as $index => $item) {
                $proformaInvoice->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [...Arr::except($item, 'id'), 'sort_order' => $index],
                );
            }
        });

        return redirect()
            ->route('admin.proforma-invoices.show', $proformaInvoice)
            ->with('success', 'Proforma invoice updated successfully.');
    }

    /**
     * Delete a proforma invoice and its line items.
     */
    public function destroy(ProformaInvoice $proformaInvoice): RedirectResponse
    {
        $proformaInvoice->delete();

        return redirect()
            ->route('admin.proforma-invoices.index')
            ->with('success', 'Proforma invoice deleted successfully.');
    }

    /**
     * Show the printable proforma invoice document.
     */
    public function print(ProformaInvoice $proformaInvoice): View
    {
        return view('admin.proforma-invoices.print', $this->invoiceData($proformaInvoice));
    }

    /**
     * Download the proforma invoice as a PDF document.
     */
    public function pdf(ProformaInvoice $proformaInvoice): Response
    {
        $pdf = Pdf::loadView('admin.proforma-invoices.pdf', $this->invoiceData($proformaInvoice))->setPaper('a4');

        return $pdf->download("proforma-invoice-{$proformaInvoice->invoice_no}.pdf");
    }

    /**
     * Download the proforma invoice as a styled Excel workbook mirroring the printed document.
     */
    public function excel(ProformaInvoice $proformaInvoice): StreamedResponse
    {
        $proformaInvoice->load(['currency', 'items']);

        [$spreadsheet, $sheet, $row] = $this->startDocumentWorkbook(
            'Proforma',
            'Proforma Invoice '.$proformaInvoice->invoice_no,
            ['A' => 12, 'B' => 40, 'C' => 15, 'D' => 15, 'E' => 14, 'F' => 20],
        );

        $currency = $proformaInvoice->currency;

        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'PROFORMA INVOICE');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('0F172A');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row += 2;

        // ---- Exporter / buyer, invoice meta, advising bank ---------------
        $partiesRow = $row;
        $this->documentSectionLabel($sheet, "A{$row}", 'EXPORTER');
        $this->documentMetaPair($sheet, $row, 'D', 'F', 'Invoice No', $proformaInvoice->invoice_no);
        $row++;

        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", $proformaInvoice->exporter_name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0F172A');
        $this->documentMetaPair($sheet, $row, 'D', 'F', 'Date', $proformaInvoice->invoice_date->format('Y/m/d'));
        $row++;

        if ($proformaInvoice->exporter_address) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}", $proformaInvoice->exporter_address);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($proformaInvoice->exporter_address, 60));
        }

        $this->documentMetaPair($sheet, $row, 'D', 'F', 'Currency', $currency->code);
        $row += 2;

        $this->documentSectionLabel($sheet, "A{$row}", 'IMPORTER / BUYER');
        $row++;

        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", $proformaInvoice->buyer_name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0F172A');
        $row++;

        if ($proformaInvoice->buyer_address) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}", $proformaInvoice->buyer_address);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($proformaInvoice->buyer_address, 60));
            $row++;
        }

        $sheet->getStyle("A{$partiesRow}:F".($row - 1))->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        $row++;

        if ($proformaInvoice->hasAdvisingBankDetails()) {
            $this->documentSectionLabel($sheet, "A{$row}", "EXPORTER'S LC ADVISING BANK");
            $row++;

            $bankRows = array_filter([
                'Bank' => $proformaInvoice->advising_bank_name,
                'Address' => $proformaInvoice->advising_bank_address,
                'SWIFT Code' => $proformaInvoice->advising_bank_swift,
                'Beneficiary Name' => $proformaInvoice->beneficiary_name,
                'Beneficiary A/C' => $proformaInvoice->beneficiary_account,
            ]);

            foreach ($bankRows as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
                $sheet->mergeCells("B{$row}:F{$row}");
                $sheet->setCellValue("B{$row}", $value);
                $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
                $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($value, 95));
                $row++;
            }

            $row++;
        }

        // ---- Shipping routing + delivery terms ----------------------------
        $shipping = array_filter([
            'Pre-Carriage' => $proformaInvoice->pre_carriage,
            'Place of Receipt' => $proformaInvoice->place_of_receipt,
            'Country of Origin' => $proformaInvoice->country_of_origin,
            'Port of Loading' => $proformaInvoice->port_of_loading,
            'Port of Discharge' => $proformaInvoice->port_of_discharge,
            'Final Destination' => $proformaInvoice->final_destination,
        ]);

        if ($shipping !== [] || $proformaInvoice->delivery_payment_terms) {
            $this->documentSectionLabel($sheet, "A{$row}", 'SHIPPING & DELIVERY');
            $row++;

            foreach ($shipping as $label => $value) {
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
                $sheet->mergeCells("C{$row}:F{$row}");
                $sheet->setCellValue("C{$row}", $value);
                $sheet->getStyle("C{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
                $row++;
            }

            if ($proformaInvoice->delivery_payment_terms) {
                $sheet->mergeCells("A{$row}:B{$row}");
                $sheet->setCellValue("A{$row}", 'Terms of Delivery and Payment');
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
                $sheet->mergeCells("C{$row}:F{$row}");
                $sheet->setCellValue("C{$row}", mb_strtoupper($proformaInvoice->delivery_payment_terms));
                $sheet->getStyle("C{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
                $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($proformaInvoice->delivery_payment_terms, 80));
                $row++;
            }

            $row++;
        }

        // ---- Description of goods -----------------------------------------
        $amountHeading = 'Total Amount ('.$currency->code.')'
            .($proformaInvoice->incoterm ? ' '.$proformaInvoice->incoterm : '');

        $this->documentTableHeader($sheet, $row, [
            'Mark',
            'Description of Goods',
            'H.S. Code No.',
            'Quantity',
            'Rate ('.$currency->code.')',
            $amountHeading,
        ]);
        $sheet->getStyle("A{$row}:A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $firstItemRow = $row;

        foreach ($proformaInvoice->items as $index => $item) {
            $sheet->setCellValue("A{$row}", $index === 0 ? $proformaInvoice->mark : null);
            $sheet->setCellValue("B{$row}", $item->description);
            $sheet->setCellValue("C{$row}", $item->hs_code);
            $sheet->setCellValue("D{$row}", $item->quantityLabel());
            $sheet->setCellValue("E{$row}", $item->rate !== null ? (float) $item->rate : null);
            $sheet->setCellValue("F{$row}", (float) $item->amount);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('1E293B');
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}:D{$row}")->getFont()->getColor()->setRGB('64748B');
            $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($item->description, 40, 15.0, 20.0));

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        $lastItemRow = $row - 1;

        if ($lastItemRow >= $firstItemRow) {
            $sheet->getStyle("E{$firstItemRow}:F{$lastItemRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("E{$firstItemRow}:F{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$firstItemRow}:F{$lastItemRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // ---- Total ---------------------------------------------------------
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL : SAY '
            .($proformaInvoice->incoterm ? '('.$proformaInvoice->incoterm.') ' : '')
            .mb_strtoupper($currency->name));
        $sheet->setCellValue("F{$row}", $proformaInvoice->totalAmount());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("A{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("F{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getStyle("F{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode('"'.$currency->symbol.'"#,##0.00');
        $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row++;

        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", $proformaInvoice->amountInWords());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');
        $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($proformaInvoice->amountInWords(), 110));
        $row += 2;

        // ---- Declaration ----------------------------------------------------
        if ($proformaInvoice->declaration) {
            $this->documentSectionLabel($sheet, "A{$row}", 'DECLARATION');
            $row++;

            $sheet->mergeCells("A{$row}:F{$row}");
            $sheet->setCellValue("A{$row}", $proformaInvoice->declaration);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($proformaInvoice->declaration, 110));
            $row += 2;
        }

        $row = $this->documentSignature(
            $sheet,
            $row,
            'D',
            'F',
            null,
            null,
            $proformaInvoice->exporter_name,
        );

        $this->finishDocumentWorkbook($sheet, $row, 'F');

        return $this->streamWorkbook($spreadsheet, "proforma-invoice-{$proformaInvoice->invoice_no}.xlsx");
    }

    /**
     * Shared view data for the show, print, and PDF documents.
     *
     * @return array<string, mixed>
     */
    private function invoiceData(ProformaInvoice $proformaInvoice): array
    {
        $proformaInvoice->load(['currency', 'items']);

        return [
            'proformaInvoice' => $proformaInvoice,
            'totalAmount' => $proformaInvoice->totalAmount(),
            'amountInWords' => $proformaInvoice->amountInWords(),
        ];
    }

    /**
     * Company-level defaults prefilled into a new proforma invoice, where each
     * value stays editable per document because the exporting entity varies.
     *
     * @return array<string, string|null>
     */
    private function documentDefaults(): array
    {
        return [
            'exporter_name' => Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group'),
            'exporter_address' => Setting::get('china_office_address') ?: Setting::get('site_address'),
            'advising_bank_name' => Setting::get('bank_name'),
            'advising_bank_swift' => Setting::get('bank_swift_code'),
            'beneficiary_name' => Setting::get('bank_account_name'),
            'beneficiary_account' => Setting::get('bank_account_number'),
            'declaration' => Setting::get('proforma_invoice_declaration'),
        ];
    }

    /**
     * Suggest the next proforma invoice number (editable by the admin).
     */
    private function suggestInvoiceNumber(): string
    {
        $next = (ProformaInvoice::max('id') ?? 0) + 1;

        do {
            $number = 'PI-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (ProformaInvoice::where('invoice_no', $number)->exists());

        return $number;
    }
}
