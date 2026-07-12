<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTransactionRequest;
use App\Http\Requests\Admin\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * List income and expense entries.
     */
    public function index(Request $request): View
    {
        $type = $request->string('type')->toString();
        $categoryId = $request->integer('category');
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $search = $request->string('search')->trim()->toString();

        $transactions = Transaction::with('category')
            ->when(TransactionType::tryFrom($type) !== null, fn ($query) => $query->where('type', $type))
            ->when($categoryId > 0, fn ($query) => $query->where('transaction_category_id', $categoryId))
            ->when($from !== '', fn ($query) => $query->whereDate('transaction_date', '>=', $from))
            ->when($to !== '', fn ($query) => $query->whereDate('transaction_date', '<=', $to))
            ->when($search !== '', fn ($query) => $query->where('description', 'like', "%{$search}%"))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'incomeCategories' => TransactionCategory::income()->orderBy('name')->get(),
            'expenseCategories' => TransactionCategory::expense()->orderBy('name')->get(),
            'type' => $type,
            'categoryId' => $categoryId,
            'from' => $from,
            'to' => $to,
            'search' => $search,
        ]);
    }

    /**
     * Store a new income or expense entry.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $transaction = Transaction::create($request->validated());

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', $transaction->type->label().' entry added successfully.');
    }

    /**
     * Update an entry.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $transaction->update($request->validated());

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', $transaction->type->label().' entry updated successfully.');
    }

    /**
     * Delete an entry.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        return redirect()
            ->route('admin.transactions.index')
            ->with('success', 'Entry deleted successfully.');
    }
}
