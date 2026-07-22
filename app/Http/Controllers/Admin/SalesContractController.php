<?php

namespace App\Http\Controllers\Admin;

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

class SalesContractController extends Controller
{
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
