<?php

namespace App\Http\Controllers\Admin;

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

class ProformaInvoiceController extends Controller
{
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
