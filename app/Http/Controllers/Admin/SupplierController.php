<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierRequest;
use App\Http\Requests\Admin\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    /**
     * Add a supplier for the customer.
     */
    public function store(StoreSupplierRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->suppliers()->create($request->validated());

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Supplier added successfully.');
    }

    /**
     * Update a customer's supplier.
     */
    public function update(UpdateSupplierRequest $request, User $customer, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove a customer's supplier.
     */
    public function destroy(User $customer, Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseItems()->exists()) {
            return back()->with('error', 'This supplier is used in purchase items and cannot be deleted.');
        }

        $supplier->delete();

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Supplier removed successfully.');
    }
}
