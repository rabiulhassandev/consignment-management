<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLcBillRequest;
use App\Http\Requests\Admin\UpdateLcBillRequest;
use App\Models\Currency;
use App\Models\LcBill;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LcBillController extends Controller
{
    /**
     * List LC bills with optional search and customer/settled filters.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $customerId = $request->integer('customer');
        $settled = $request->string('settled')->toString();

        $lcBills = LcBill::with(['customer', 'currency'])
            ->withSum(['entries as received_sum' => fn ($query) => $query->where('type', EntryType::Received)], 'amount')
            ->withSum(['entries as paid_sum' => fn ($query) => $query->where('type', EntryType::Paid)], 'amount')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('bill_no', 'like', "%{$search}%")
                    ->orWhere('lc_number', 'like', "%{$search}%"),
            ))
            ->when($customerId > 0, fn ($query) => $query->where('customer_id', $customerId))
            ->when($settled !== '', fn ($query) => $query->where('is_settled', $settled === '1'))
            ->latest('bill_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.lc-bills.index', [
            'lcBills' => $lcBills,
            'customers' => User::customers()->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'customerId' => $customerId,
            'settled' => $settled,
        ]);
    }

    /**
     * Show the form to create an LC bill.
     */
    public function create(Request $request): View
    {
        return view('admin.lc-bills.create', [
            'suggestedNumber' => $this->suggestBillNumber(),
            'preselectedCustomerId' => $request->integer('customer'),
            ...$this->formData(),
        ]);
    }

    /**
     * Store an LC bill and its received/paid entries.
     */
    public function store(StoreLcBillRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $lcBill = DB::transaction(function () use ($request, $validated) {
            $lcBill = LcBill::create([
                ...Arr::except($validated, ['receipts', 'payments', 'is_settled']),
                'is_settled' => $request->boolean('is_settled'),
            ]);

            $lcBill->entries()->createMany(
                $this->entryRows($validated)->map(fn (array $entry): array => Arr::except($entry, 'id')),
            );

            return $lcBill;
        });

        return redirect()
            ->route('admin.lc-bills.show', $lcBill)
            ->with('success', 'LC bill created successfully.');
    }

    /**
     * Show an LC bill with its received/paid ledger and totals.
     */
    public function show(LcBill $lcBill): View
    {
        return view('admin.lc-bills.show', $this->billData($lcBill));
    }

    /**
     * Show the form to edit an LC bill.
     */
    public function edit(LcBill $lcBill): View
    {
        $lcBill->load('entries');

        return view('admin.lc-bills.edit', [
            'lcBill' => $lcBill,
            ...$this->formData(),
        ]);
    }

    /**
     * Update an LC bill and sync its entries on both sides.
     */
    public function update(UpdateLcBillRequest $request, LcBill $lcBill): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated, $lcBill) {
            $lcBill->update([
                ...Arr::except($validated, ['receipts', 'payments', 'is_settled']),
                'is_settled' => $request->boolean('is_settled'),
            ]);

            $entries = $this->entryRows($validated);

            $lcBill->entries()
                ->whereNotIn('id', $entries->pluck('id')->filter())
                ->delete();

            foreach ($entries as $entry) {
                $lcBill->entries()->updateOrCreate(
                    ['id' => $entry['id'] ?? null],
                    Arr::except($entry, 'id'),
                );
            }
        });

        return redirect()
            ->route('admin.lc-bills.show', $lcBill)
            ->with('success', 'LC bill updated successfully.');
    }

    /**
     * Delete an LC bill and its entries.
     */
    public function destroy(LcBill $lcBill): RedirectResponse
    {
        $lcBill->delete();

        return redirect()
            ->route('admin.lc-bills.index')
            ->with('success', 'LC bill deleted successfully.');
    }

    /**
     * Show the printable LC bill document.
     */
    public function print(LcBill $lcBill): View
    {
        return view('admin.lc-bills.print', $this->billData($lcBill));
    }

    /**
     * Download the LC bill as a PDF document.
     */
    public function pdf(LcBill $lcBill): Response
    {
        $pdf = Pdf::loadView('admin.lc-bills.pdf', $this->billData($lcBill))->setPaper('a4');

        return $pdf->download("lc-bill-{$lcBill->bill_no}.pdf");
    }

    /**
     * Shared view data for the show and print pages.
     *
     * @return array<string, mixed>
     */
    private function billData(LcBill $lcBill): array
    {
        $lcBill->load(['customer', 'currency', 'entries']);

        return [
            'lcBill' => $lcBill,
            'receipts' => $lcBill->entries->where('type', EntryType::Received)->values(),
            'payments' => $lcBill->entries->where('type', EntryType::Paid)->values(),
            'totalReceived' => $lcBill->totalReceived(),
            'totalPaid' => $lcBill->totalPaid(),
            'balance' => $lcBill->balance(),
            'localDue' => $lcBill->localDue(),
        ];
    }

    /**
     * Merge validated receipt and payment rows into entry payloads with type and order.
     *
     * @param  array<string, mixed>  $validated
     * @return Collection<int, array<string, mixed>>
     */
    private function entryRows(array $validated): Collection
    {
        $mapSide = fn (array $rows, EntryType $type): Collection => collect($rows)->values()->map(
            fn (array $entry, int $index): array => [
                ...$entry,
                'type' => $type,
                'sort_order' => $index,
            ],
        );

        return $mapSide($validated['receipts'] ?? [], EntryType::Received)
            ->concat($mapSide($validated['payments'] ?? [], EntryType::Paid));
    }

    /**
     * Shared dropdown data for the LC bill form.
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

    /**
     * Suggest the next LC bill number (editable by the admin).
     */
    private function suggestBillNumber(): string
    {
        $next = (LcBill::max('id') ?? 0) + 1;

        do {
            $number = 'LCB-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (LcBill::where('bill_no', $number)->exists());

        return $number;
    }
}
