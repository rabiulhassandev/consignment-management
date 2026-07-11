<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the customer portal dashboard.
     */
    public function __invoke(Request $request): View
    {
        $customer = $request->user();

        return view('portal.dashboard', [
            'customer' => $customer,
            'totalConsignments' => $customer->consignments()->count(),
            'totalItems' => PurchaseItem::whereHas(
                'consignment',
                fn ($query) => $query->whereBelongsTo($customer, 'customer'),
            )->count(),
            'recentConsignments' => $customer->consignments()
                ->with('currency')
                ->withCount('items')
                ->latest('consignment_date')
                ->limit(5)
                ->get(),
        ]);
    }
}
