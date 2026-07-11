<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * List categories.
     */
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount(['suppliers', 'purchaseItems'])->orderBy('name')->paginate(15),
        ]);
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update a category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->suppliers()->exists() || $category->purchaseItems()->exists()) {
            return back()->with('error', 'This category is in use and cannot be deleted.');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
