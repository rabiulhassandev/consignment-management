<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsignmentRequest;
use App\Http\Requests\Admin\UpdateConsignmentRequest;
use App\Models\Category;
use App\Models\Consignment;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    /**
     * List consignments with optional customer filter and search.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $customerId = $request->integer('customer');

        $consignments = Consignment::with(['customer', 'currency'])
            ->withCount('items')
            ->withSum('items', 'amount')
            ->when($search !== '', fn ($query) => $query->where('consignment_no', 'like', "%{$search}%"))
            ->when($customerId > 0, fn ($query) => $query->where('customer_id', $customerId))
            ->latest('consignment_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.consignments.index', [
            'consignments' => $consignments,
            'customers' => User::customers()->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'customerId' => $customerId,
        ]);
    }

    /**
     * Show the form to create a consignment for a customer.
     */
    public function create(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        return view('admin.consignments.create', [
            'customer' => $customer,
            'suggestedNumber' => $this->suggestConsignmentNumber(),
            ...$this->formData($customer),
        ]);
    }

    /**
     * Store a consignment and its purchase items.
     */
    public function store(StoreConsignmentRequest $request, User $customer): RedirectResponse
    {
        $validated = $request->validated();

        $consignment = DB::transaction(function () use ($validated, $customer) {
            $consignment = $customer->consignments()->create(Arr::except($validated, 'items'));

            $consignment->items()->createMany(
                collect($validated['items'])->map(fn (array $item): array => Arr::except($item, 'id')),
            );

            return $consignment;
        });

        return redirect()
            ->route('admin.consignments.show', $consignment)
            ->with('success', 'Consignment created successfully.');
    }

    /**
     * Show a consignment with its purchase items.
     */
    public function show(Consignment $consignment): View
    {
        $consignment->load(['customer', 'currency', 'items.category', 'items.supplier']);

        return view('admin.consignments.show', [
            'consignment' => $consignment,
            'totalAmount' => $consignment->items->sum('amount'),
        ]);
    }

    /**
     * Show the form to edit a consignment.
     */
    public function edit(Consignment $consignment): View
    {
        $consignment->load('items');

        return view('admin.consignments.edit', [
            'consignment' => $consignment,
            'customer' => $consignment->customer,
            ...$this->formData($consignment->customer),
        ]);
    }

    /**
     * Update a consignment and sync its purchase items.
     */
    public function update(UpdateConsignmentRequest $request, Consignment $consignment): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $consignment) {
            $consignment->update(Arr::except($validated, 'items'));

            $items = collect($validated['items']);

            $consignment->items()
                ->whereNotIn('id', $items->pluck('id')->filter())
                ->delete();

            foreach ($items as $item) {
                $consignment->items()->updateOrCreate(
                    ['id' => $item['id'] ?? null],
                    Arr::except($item, 'id'),
                );
            }
        });

        return redirect()
            ->route('admin.consignments.show', $consignment)
            ->with('success', 'Consignment updated successfully.');
    }

    /**
     * Delete a consignment and its purchase items.
     */
    public function destroy(Consignment $consignment): RedirectResponse
    {
        $consignment->delete();

        return redirect()
            ->route('admin.consignments.index')
            ->with('success', 'Consignment deleted successfully.');
    }

    /**
     * Shared dropdown data for the consignment form.
     *
     * @return array<string, mixed>
     */
    private function formData(User $customer): array
    {
        return [
            'currencies' => Currency::active()->orderBy('code')->get(),
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => $customer->suppliers()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * Suggest the next consignment number (editable by the admin).
     */
    private function suggestConsignmentNumber(): string
    {
        $next = (Consignment::max('id') ?? 0) + 1;

        do {
            $number = 'CN-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Consignment::where('consignment_no', $number)->exists());

        return $number;
    }
}
