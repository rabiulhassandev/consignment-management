<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCurrencyRequest;
use App\Http\Requests\Admin\UpdateCurrencyRequest;
use App\Models\Currency;
use App\Models\TtAccountEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    /**
     * List currencies.
     */
    public function index(): View
    {
        return view('admin.currencies.index', [
            'currencies' => Currency::withCount('consignments')->orderBy('code')->paginate(15),
        ]);
    }

    /**
     * Store a new currency.
     */
    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        Currency::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.currencies.index')
            ->with('success', 'Currency created successfully.');
    }

    /**
     * Update a currency.
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $currency->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.currencies.index')
            ->with('success', 'Currency updated successfully.');
    }

    /**
     * Delete a currency.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        $inUse = $currency->consignments()->exists()
            || $currency->invoices()->exists()
            || $currency->lcBills()->exists()
            || $currency->ttAccounts()->exists()
            || TtAccountEntry::where('source_currency_id', $currency->id)->exists();

        if ($inUse) {
            return back()->with('error', 'This currency is in use and cannot be deleted.');
        }

        $currency->delete();

        return redirect()
            ->route('admin.currencies.index')
            ->with('success', 'Currency deleted successfully.');
    }
}
