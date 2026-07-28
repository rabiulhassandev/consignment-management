<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\BuildsDocumentWorkbook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSalesContractRequest;
use App\Http\Requests\Admin\UpdateSalesContractRequest;
use App\Models\Currency;
use App\Models\SalesContract;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesContractController extends Controller
{
    use BuildsDocumentWorkbook;

    /**
     * List sales contracts with optional search by contract number or buyer.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $salesContracts = SalesContract::with('currency')
            ->withCount('items')
            ->withSum('items', 'amount')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('contract_no', 'like', "%{$search}%")
                    ->orWhere('buyer', 'like', "%{$search}%"),
            ))
            ->latest('contract_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sales-contracts.index', [
            'salesContracts' => $salesContracts,
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create a sales contract.
     */
    public function create(): View
    {
        return view('admin.sales-contracts.create', [
            'suggestedNumber' => $this->suggestContractNumber(),
            'defaultTerms' => Setting::get('sales_contract_terms', ''),
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Store a sales contract and its line items.
     */
    public function store(StoreSalesContractRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $salesContract = DB::transaction(function () use ($validated) {
            $salesContract = SalesContract::create(Arr::except($validated, 'items'));

            $salesContract->items()->createMany(
                collect($validated['items'])->values()->map(
                    fn (array $item, int $index): array => [...Arr::except($item, 'id'), 'sort_order' => $index],
                ),
            );

            return $salesContract;
        });

        return redirect()
            ->route('admin.sales-contracts.show', $salesContract)
            ->with('success', 'Sales contract created successfully.');
    }

    /**
     * Show a sales contract with its line items.
     */
    public function show(SalesContract $salesContract): View
    {
        return view('admin.sales-contracts.show', $this->contractData($salesContract));
    }

    /**
     * Show the form to edit a sales contract.
     */
    public function edit(SalesContract $salesContract): View
    {
        $salesContract->load('items');

        return view('admin.sales-contracts.edit', [
            'salesContract' => $salesContract,
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Update a sales contract and sync its line items.
     */
    public function update(UpdateSalesContractRequest $request, SalesContract $salesContract): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $salesContract) {
            $salesContract->update(Arr::except($validated, 'items'));

            $items = collect($validated['items'])->values();

            $salesContract->items()
                ->whereNotIn('id', $items->pluck('id')->filter())
                ->delete();

            foreach ($items as $index => $item) {
                $salesContract->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    [...Arr::except($item, 'id'), 'sort_order' => $index],
                );
            }
        });

        return redirect()
            ->route('admin.sales-contracts.show', $salesContract)
            ->with('success', 'Sales contract updated successfully.');
    }

    /**
     * Delete a sales contract and its line items.
     */
    public function destroy(SalesContract $salesContract): RedirectResponse
    {
        $salesContract->delete();

        return redirect()
            ->route('admin.sales-contracts.index')
            ->with('success', 'Sales contract deleted successfully.');
    }

    /**
     * Show the printable sales contract document.
     */
    public function print(SalesContract $salesContract): View
    {
        return view('admin.sales-contracts.print', $this->contractData($salesContract));
    }

    /**
     * Download the sales contract as a PDF document.
     */
    public function pdf(SalesContract $salesContract): Response
    {
        $pdf = Pdf::loadView('admin.sales-contracts.pdf', $this->contractData($salesContract))->setPaper('a4');

        return $pdf->download("sales-contract-{$salesContract->contract_no}.pdf");
    }

    /**
     * Download the sales contract as a styled Excel workbook mirroring the printed document.
     */
    public function excel(SalesContract $salesContract): StreamedResponse
    {
        $salesContract->load(['currency', 'items']);

        [$spreadsheet, $sheet, $row] = $this->startDocumentWorkbook(
            'Sales Contract',
            'Sales Contract '.$salesContract->contract_no,
            ['A' => 7, 'B' => 34, 'C' => 14, 'D' => 11, 'E' => 10, 'F' => 14, 'G' => 17],
        );

        $currency = $salesContract->currency;
        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');

        // ---- Buyer + contract meta ---------------------------------------
        $this->documentSectionLabel($sheet, "A{$row}", 'BUYER');
        $this->documentMetaPair($sheet, $row, 'E', 'G', 'Contract No.', $salesContract->contract_no);
        $row++;

        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", $salesContract->buyer);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($row)->setRowHeight(20);
        $this->documentMetaPair($sheet, $row, 'E', 'G', 'Date', $salesContract->contract_date->format('d F Y'));
        $row++;

        $this->documentMetaPair($sheet, $row, 'E', 'G', 'Currency', $currency->code);

        if ($salesContract->buyer_address) {
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", $salesContract->buyer_address);
            $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($salesContract->buyer_address, 60));
        }

        $row += 2;

        // ---- Items --------------------------------------------------------
        $this->documentTableHeader($sheet, $row, [
            'Sl. No.',
            'Description',
            'H.S. Code',
            'Quantity',
            'Unit',
            'Unit / '.$currency->code,
            'Total / '.$currency->code,
        ]);
        $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        $firstItemRow = $row;

        foreach ($salesContract->items as $index => $item) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $item->description);
            $sheet->setCellValue("C{$row}", $item->hs_code);
            $sheet->setCellValue("D{$row}", $item->quantity !== null ? (float) $item->quantity : null);
            $sheet->setCellValue("E{$row}", $item->unit ? mb_strtoupper($item->unit) : null);
            $sheet->setCellValue("F{$row}", $item->unit_price !== null ? (float) $item->unit_price : null);
            $sheet->setCellValue("G{$row}", (float) $item->amount);

            $sheet->getStyle("A{$row}:A{$row}")->getFont()->getColor()->setRGB('94A3B8');
            $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("C{$row}:E{$row}")->getFont()->getColor()->setRGB('64748B');
            $sheet->getStyle("G{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($item->description, 34, 15.0, 20.0));

            if ($index % 2 === 1) {
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            $row++;
        }

        if ($salesContract->freight_charge !== null) {
            $sheet->setCellValue("B{$row}", 'FREIGHT CHARGE');
            $sheet->setCellValue("F{$row}", (float) $salesContract->freight_charge);
            $sheet->setCellValue("G{$row}", (float) $salesContract->freight_charge);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('1E293B');
            $sheet->getStyle("G{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $row++;
        }

        $lastItemRow = $row - 1;

        if ($lastItemRow >= $firstItemRow) {
            $sheet->getStyle("D{$firstItemRow}:D{$lastItemRow}")->getNumberFormat()->setFormatCode('#,##0.##');
            $sheet->getStyle("F{$firstItemRow}:G{$lastItemRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("F{$firstItemRow}:G{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$firstItemRow}:G{$lastItemRow}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }

        // ---- Total ---------------------------------------------------------
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL AMOUNT');
        $sheet->setCellValue("G{$row}", $salesContract->totalAmount());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->getStyle("A{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("G{$row}")->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0F172A');
        $sheet->getStyle("G{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('"'.$currency->symbol.'"#,##0.00');
        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('1E293B');
        $sheet->getRowDimension($row)->setRowHeight(26);
        $row += 2;

        // ---- Amount in words -------------------------------------------------
        $sheet->setCellValue("A{$row}", 'In Words');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('64748B');
        $sheet->mergeCells("B{$row}:G{$row}");
        $sheet->setCellValue("B{$row}", $salesContract->amountInWords());
        $sheet->getStyle("B{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('0F172A');
        $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($salesContract->amountInWords(), 100));
        $row += 2;

        // ---- Terms & conditions ----------------------------------------------
        $termLines = $salesContract->termLines();

        if ($termLines->isNotEmpty()) {
            $this->documentSectionLabel($sheet, "A{$row}", 'TERMS AND CONDITION:');
            $row++;

            foreach ($termLines as $index => $line) {
                $sheet->setCellValueExplicit("A{$row}", ($index + 1).'.', DataType::TYPE_STRING);
                $sheet->getStyle("A{$row}")->getFont()->setSize(10)->getColor()->setRGB('94A3B8');
                $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->mergeCells("B{$row}:G{$row}");
                $sheet->setCellValue("B{$row}", $line);
                $sheet->getStyle("B{$row}")->getFont()->setSize(10)->getColor()->setRGB('64748B');
                $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($line, 100));
                $row++;
            }

            $row++;
        }

        // ---- Seller / buyer confirmation ---------------------------------------
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->mergeCells("D{$row}:G{$row}");
        $sheet->setCellValue("A{$row}", 'Seller Confirmation');
        $sheet->setCellValue("D{$row}", 'Buyer Confirmation');
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A{$row}:G{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $sheet->getStyle("A{$row}:G{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $signingRow = $row;
        $sheet->getRowDimension($signingRow)->setRowHeight(70);
        $row++;

        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'For '.$companyName);
        $sheet->mergeCells("D{$row}:G{$row}");
        $sheet->setCellValue("D{$row}", 'For '.$salesContract->buyer);
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setSize(9)->getColor()->setRGB('94A3B8');
        $sheet->getStyle("A{$row}:G{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight('For '.$companyName, 40));

        $sheet->getStyle("A{$signingRow}:C{$row}")->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('1E293B');
        $sheet->getStyle("D{$signingRow}:G{$row}")->getBorders()->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('1E293B');
        $row += 2;

        $this->finishDocumentWorkbook($sheet, $row, 'G');

        return $this->streamWorkbook($spreadsheet, "sales-contract-{$salesContract->contract_no}.xlsx");
    }

    /**
     * Shared view data for the show, print, and PDF documents.
     *
     * @return array<string, mixed>
     */
    private function contractData(SalesContract $salesContract): array
    {
        $salesContract->load(['currency', 'items']);

        return [
            'salesContract' => $salesContract,
            'itemsTotal' => $salesContract->itemsTotal(),
            'totalAmount' => $salesContract->totalAmount(),
            'amountInWords' => $salesContract->amountInWords(),
            'termLines' => $salesContract->termLines(),
        ];
    }

    /**
     * Suggest the next contract number (editable by the admin).
     */
    private function suggestContractNumber(): string
    {
        $next = (SalesContract::max('id') ?? 0) + 1;

        do {
            $number = 'SC-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (SalesContract::where('contract_no', $number)->exists());

        return $number;
    }
}
