<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTtAccountEntryRequest;
use App\Http\Requests\Admin\UpdateTtAccountEntryRequest;
use App\Models\TtAccount;
use App\Models\TtAccountEntry;
use Illuminate\Http\RedirectResponse;

class TtAccountEntryController extends Controller
{
    /**
     * Add an entry to a TT account statement.
     */
    public function store(StoreTtAccountEntryRequest $request, TtAccount $ttAccount): RedirectResponse
    {
        $ttAccount->entries()->create($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'Entry added successfully.');
    }

    /**
     * Update a TT account entry.
     */
    public function update(UpdateTtAccountEntryRequest $request, TtAccount $ttAccount, TtAccountEntry $entry): RedirectResponse
    {
        $entry->update($request->validated());

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'Entry updated successfully.');
    }

    /**
     * Delete a TT account entry.
     */
    public function destroy(TtAccount $ttAccount, TtAccountEntry $entry): RedirectResponse
    {
        $entry->delete();

        return redirect()
            ->route('admin.tt-accounts.show', $ttAccount)
            ->with('success', 'Entry deleted successfully.');
    }
}
