<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EntryType;
use App\Enums\TtAccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTtAccountRequest;
use App\Http\Requests\Admin\UpdateTtAccountRequest;
use App\Models\Currency;
use App\Models\TtAccount;
use App\Models\TtAccountEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TtAccountController extends Controller
{
    /**
     * List TT accounts with optional search and customer/status filters.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $customerId = $request->integer('customer');
        $status = $request->string('status')->toString();

        $ttAccounts = TtAccount::with(['customer', 'currency'])
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($customerId > 0, fn ($query) => $query->where('customer_id', $customerId))
            ->when(TtAccountStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tt-accounts.index', [
            'ttAccounts' => $ttAccounts,
            'customers' => User::customers()->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'customerId' => $customerId,
            'status' => $status,
        ]);
    }

    /**
     * Show the form to create a TT account.
     */
    public function create(Request $request): View
    {
        return view('admin.tt-accounts.create', [
            'preselectedCustomerId' => $request->integer('customer'),
            ...$this->formData(),
        ]);
    }

    /**
     * Store a TT account.
     */
    public function store(StoreTtAccountRequest $request): RedirectResponse
    {
        $ttAccount = TtAccount::create($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'TT account created successfully.');
    }

    /**
     * Show a TT account statement with its running balance.
     */
    public function show(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.show', [
            ...$this->statementData($ttAccount),
            'currencies' => Currency::active()->orderBy('code')->get(),
        ]);
    }

    /**
     * Show the form to edit a TT account.
     */
    public function edit(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.edit', [
            'ttAccount' => $ttAccount,
            ...$this->formData(),
        ]);
    }

    /**
     * Update a TT account.
     */
    public function update(UpdateTtAccountRequest $request, TtAccount $ttAccount): RedirectResponse
    {
        $ttAccount->update($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'TT account updated successfully.');
    }

    /**
     * Delete a TT account and its entries.
     */
    public function destroy(TtAccount $ttAccount): RedirectResponse
    {
        $ttAccount->delete();

        return redirect()
            ->route('admin.tt-accounts.index')
            ->with('success', 'TT account deleted successfully.');
    }

    /**
     * Show the printable account statement.
     */
    public function print(TtAccount $ttAccount): View
    {
        return view('admin.tt-accounts.print', $this->statementData($ttAccount));
    }

    /**
     * Shared statement data with the running balance computed in insertion
     * (id) order — many rows carry no date, so the balance follows the order
     * entries were recorded rather than the display-only entry date.
     *
     * @return array<string, mixed>
     */
    private function statementData(TtAccount $ttAccount): array
    {
        $ttAccount->load(['customer', 'currency', 'entries.sourceCurrency']);

        $running = (float) ($ttAccount->opening_balance ?? 0);

        $entries = $ttAccount->entries->map(function (TtAccountEntry $entry) use (&$running): TtAccountEntry {
            $signed = $entry->type === EntryType::Received ? (float) $entry->amount : -(float) $entry->amount;
            $entry->running_balance = $running = round($running + $signed, 2);

            return $entry;
        });

        return [
            'ttAccount' => $ttAccount,
            'entries' => $entries,
            'totalReceived' => (float) $ttAccount->entries->where('type', EntryType::Received)->sum('amount'),
            'totalPaid' => (float) $ttAccount->entries->where('type', EntryType::Paid)->sum('amount'),
            'closingBalance' => $running,
        ];
    }

    /**
     * Shared dropdown data for the TT account form.
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
}
