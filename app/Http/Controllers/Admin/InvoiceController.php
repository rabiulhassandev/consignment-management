<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvoiceRequest;
use App\Http\Requests\Admin\UpdateInvoiceRequest;
use App\Models\Currency;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
