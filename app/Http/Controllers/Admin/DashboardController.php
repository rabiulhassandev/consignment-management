<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalCustomers' => User::customers()->count(),
            'pendingCustomers' => User::customers()->where('status', UserStatus::Pending)->count(),
            'totalSuppliers' => Supplier::count(),
            'totalConsignments' => Consignment::count(),
            'recentCustomers' => User::customers()->latest()->limit(5)->get(),
            'recentConsignments' => Consignment::with(['customer', 'currency'])
                ->withCount('items')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
