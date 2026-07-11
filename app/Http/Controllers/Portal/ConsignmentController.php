<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Consignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    /**
     * List the authenticated customer's consignments.
     */
    public function index(Request $request): View
    {
        $consignments = $request->user()->consignments()
            ->with('currency')
            ->withCount('items')
            ->withSum('items', 'amount')
            ->latest('consignment_date')
            ->latest('id')
            ->paginate(15);

        return view('portal.consignments.index', ['consignments' => $consignments]);
    }

    /**
     * Show one of the authenticated customer's consignments.
     */
    public function show(Request $request, Consignment $consignment): View
    {
        abort_unless($consignment->customer_id === $request->user()->id, 404);

        $consignment->load(['currency', 'items.category', 'items.supplier']);

        return view('portal.consignments.show', [
            'consignment' => $consignment,
            'totalAmount' => $consignment->items->sum('amount'),
        ]);
    }
}
