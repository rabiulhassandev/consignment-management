<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Portal;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'approved', 'staff'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');
        Route::post('notifications/mark-all-read', [Admin\NotificationController::class, 'markAllRead'])
            ->name('notifications.read');

        Route::resource('users', Admin\UserController::class)
            ->except('show')
            ->middleware('permission:users.manage');

        Route::resource('roles', Admin\RoleController::class)
            ->except('show')
            ->middleware('permission:roles.manage');

        Route::middleware('permission:customers.view')->group(function () {
            Route::get('customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [Admin\CustomerController::class, 'show'])
                ->whereNumber('customer')
                ->name('customers.show');
        });

        Route::middleware('permission:customers.edit')->group(function () {
            Route::get('customers/{customer}/edit', [Admin\CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [Admin\CustomerController::class, 'update'])->name('customers.update');
        });

        Route::middleware('permission:customers.approve')->group(function () {
            Route::patch('customers/{customer}/approve', [Admin\CustomerController::class, 'approve'])->name('customers.approve');
            Route::patch('customers/{customer}/reject', [Admin\CustomerController::class, 'reject'])->name('customers.reject');
        });

        Route::post('customers/{customer}/suppliers', [Admin\SupplierController::class, 'store'])
            ->middleware('permission:suppliers.create')
            ->name('customers.suppliers.store');
        Route::put('customers/{customer}/suppliers/{supplier}', [Admin\SupplierController::class, 'update'])
            ->middleware('permission:suppliers.edit')
            ->scopeBindings()
            ->name('customers.suppliers.update');
        Route::delete('customers/{customer}/suppliers/{supplier}', [Admin\SupplierController::class, 'destroy'])
            ->middleware('permission:suppliers.delete')
            ->scopeBindings()
            ->name('customers.suppliers.destroy');

        Route::resource('categories', Admin\CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:categories.manage');

        Route::resource('currencies', Admin\CurrencyController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:currencies.manage');

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        });

        Route::middleware('permission:consignments.view')->group(function () {
            Route::get('search', Admin\SearchController::class)->name('search');
            Route::get('consignments', [Admin\ConsignmentController::class, 'index'])->name('consignments.index');
            Route::get('consignments/{consignment}', [Admin\ConsignmentController::class, 'show'])
                ->whereNumber('consignment')
                ->name('consignments.show');
        });

        Route::middleware('permission:consignments.create')->group(function () {
            Route::get('customers/{customer}/consignments/create', [Admin\ConsignmentController::class, 'create'])
                ->name('customers.consignments.create');
            Route::post('customers/{customer}/consignments', [Admin\ConsignmentController::class, 'store'])
                ->name('customers.consignments.store');
        });

        Route::middleware('permission:consignments.edit')->group(function () {
            Route::get('consignments/{consignment}/edit', [Admin\ConsignmentController::class, 'edit'])->name('consignments.edit');
            Route::put('consignments/{consignment}', [Admin\ConsignmentController::class, 'update'])->name('consignments.update');
        });

        Route::delete('consignments/{consignment}', [Admin\ConsignmentController::class, 'destroy'])
            ->middleware('permission:consignments.delete')
            ->name('consignments.destroy');
    });

Route::middleware(['auth', 'approved', 'customer'])
    ->prefix('portal')
    ->as('portal.')
    ->group(function () {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('consignments', [Portal\ConsignmentController::class, 'index'])->name('consignments.index');
        Route::get('consignments/{consignment}', [Portal\ConsignmentController::class, 'show'])->name('consignments.show');
    });
