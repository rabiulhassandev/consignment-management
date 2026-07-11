<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Global search by sample number, own sample number or consignment number.
     */
    public function __invoke(Request $request): View
    {
        $query = $request->string('q')->trim()->toString();

        $items = collect();
        $consignments = collect();

        if ($query !== '') {
            $items = PurchaseItem::with(['consignment.customer', 'consignment.currency', 'supplier', 'category'])
                ->where(function ($builder) use ($query) {
                    $builder->where('sample_number', 'like', "%{$query}%")
                        ->orWhere('own_sample_number', 'like', "%{$query}%");
                })
                ->latest('purchase_date')
                ->limit(50)
                ->get();

            $consignments = Consignment::with(['customer', 'currency'])
                ->withCount('items')
                ->withSum('items', 'amount')
                ->where('consignment_no', 'like', "%{$query}%")
                ->latest('consignment_date')
                ->limit(20)
                ->get();
        }

        return view('admin.search', [
            'query' => $query,
            'items' => $items,
            'consignments' => $consignments,
        ]);
    }
}
