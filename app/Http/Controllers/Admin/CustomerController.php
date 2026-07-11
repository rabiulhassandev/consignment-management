<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Category;
use App\Models\PurchaseItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * List customers with optional search and status filter.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = UserStatus::tryFrom($request->string('status')->toString());

        $customers = User::customers()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->withCount(['suppliers', 'consignments'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Show the customer profile dashboard.
     */
    public function show(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->loadCount(['suppliers', 'consignments']);

        return view('admin.customers.show', [
            'customer' => $customer,
            'purchaseItemsCount' => PurchaseItem::whereHas(
                'consignment',
                fn ($query) => $query->whereBelongsTo($customer, 'customer'),
            )->count(),
            'suppliers' => $customer->suppliers()->with('category')->orderBy('name')
                ->paginate(10, pageName: 'suppliers_page'),
            'consignments' => $customer->consignments()->with('currency')->withCount('items')
                ->latest('consignment_date')->paginate(10, pageName: 'consignments_page'),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for editing a customer's basic info.
     */
    public function edit(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        return view('admin.customers.edit', ['customer' => $customer]);
    }

    /**
     * Update a customer's basic info.
     */
    public function update(UpdateCustomerRequest $request, User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->update($request->validated());

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Approve a pending customer.
     */
    public function approve(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->update(['status' => UserStatus::Approved]);

        return back()->with('success', "{$customer->name} has been approved and can now log in.");
    }

    /**
     * Reject a customer.
     */
    public function reject(User $customer): RedirectResponse
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->update(['status' => UserStatus::Rejected]);

        return back()->with('success', "{$customer->name} has been rejected.");
    }
}
