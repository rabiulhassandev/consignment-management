<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTransactionCategoryRequest;
use App\Http\Requests\Admin\UpdateTransactionCategoryRequest;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionCategoryController extends Controller
{
    /**
     * List income and expense categories.
     */
    public function index(): View
    {
        return view('admin.transaction-categories.index', [
            'incomeCategories' => TransactionCategory::income()->withCount('transactions')->orderBy('name')->get(),
            'expenseCategories' => TransactionCategory::expense()->withCount('transactions')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(StoreTransactionCategoryRequest $request): RedirectResponse
    {
        TransactionCategory::create($request->validated());

        return redirect()
            ->route('admin.transaction-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update a category.
     */
    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): RedirectResponse
    {
        $transactionCategory->update($request->validated());

        return redirect()
            ->route('admin.transaction-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroy(TransactionCategory $transactionCategory): RedirectResponse
    {
        if ($transactionCategory->transactions()->exists()) {
            return back()->with('error', 'This category is in use and cannot be deleted.');
        }

        $transactionCategory->delete();

        return redirect()
            ->route('admin.transaction-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
