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

        Route::middleware('permission:customers.create')->group(function () {
            Route::get('customers/create', [Admin\CustomerController::class, 'create'])->name('customers.create');
            Route::post('customers', [Admin\CustomerController::class, 'store'])->name('customers.store');
        });

        Route::middleware('permission:customers.edit')->group(function () {
            Route::get('customers/{customer}/edit', [Admin\CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [Admin\CustomerController::class, 'update'])->name('customers.update');
            Route::patch('customers/{customer}/password', [Admin\CustomerController::class, 'updatePassword'])
                ->name('customers.password.update');
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

        Route::middleware('permission:invoices.view')->group(function () {
            Route::get('invoices', [Admin\InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}', [Admin\InvoiceController::class, 'show'])
                ->whereNumber('invoice')
                ->name('invoices.show');
            Route::get('invoices/{invoice}/print', [Admin\InvoiceController::class, 'print'])
                ->whereNumber('invoice')
                ->name('invoices.print');
            Route::get('invoices/{invoice}/pdf', [Admin\InvoiceController::class, 'pdf'])
                ->whereNumber('invoice')
                ->name('invoices.pdf');
        });

        Route::middleware('permission:invoices.create')->group(function () {
            Route::get('invoices/create', [Admin\InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('invoices', [Admin\InvoiceController::class, 'store'])->name('invoices.store');
        });

        Route::middleware('permission:invoices.edit')->group(function () {
            Route::get('invoices/{invoice}/edit', [Admin\InvoiceController::class, 'edit'])->name('invoices.edit');
            Route::put('invoices/{invoice}', [Admin\InvoiceController::class, 'update'])->name('invoices.update');
        });

        Route::delete('invoices/{invoice}', [Admin\InvoiceController::class, 'destroy'])
            ->middleware('permission:invoices.delete')
            ->name('invoices.destroy');

        Route::middleware('permission:sales-contracts.view')->group(function () {
            Route::get('sales-contracts', [Admin\SalesContractController::class, 'index'])->name('sales-contracts.index');
            Route::get('sales-contracts/{salesContract}', [Admin\SalesContractController::class, 'show'])
                ->whereNumber('salesContract')
                ->name('sales-contracts.show');
            Route::get('sales-contracts/{salesContract}/print', [Admin\SalesContractController::class, 'print'])
                ->whereNumber('salesContract')
                ->name('sales-contracts.print');
            Route::get('sales-contracts/{salesContract}/pdf', [Admin\SalesContractController::class, 'pdf'])
                ->whereNumber('salesContract')
                ->name('sales-contracts.pdf');
        });

        Route::middleware('permission:sales-contracts.create')->group(function () {
            Route::get('sales-contracts/create', [Admin\SalesContractController::class, 'create'])->name('sales-contracts.create');
            Route::post('sales-contracts', [Admin\SalesContractController::class, 'store'])->name('sales-contracts.store');
        });

        Route::middleware('permission:sales-contracts.edit')->group(function () {
            Route::get('sales-contracts/{salesContract}/edit', [Admin\SalesContractController::class, 'edit'])->name('sales-contracts.edit');
            Route::put('sales-contracts/{salesContract}', [Admin\SalesContractController::class, 'update'])->name('sales-contracts.update');
        });

        Route::delete('sales-contracts/{salesContract}', [Admin\SalesContractController::class, 'destroy'])
            ->middleware('permission:sales-contracts.delete')
            ->name('sales-contracts.destroy');

        Route::middleware('permission:lc-bills.view')->group(function () {
            Route::get('lc-bills', [Admin\LcBillController::class, 'index'])->name('lc-bills.index');
            Route::get('lc-bills/{lcBill}', [Admin\LcBillController::class, 'show'])
                ->whereNumber('lcBill')
                ->name('lc-bills.show');
            Route::get('lc-bills/{lcBill}/print', [Admin\LcBillController::class, 'print'])
                ->whereNumber('lcBill')
                ->name('lc-bills.print');
            Route::get('lc-bills/{lcBill}/pdf', [Admin\LcBillController::class, 'pdf'])
                ->whereNumber('lcBill')
                ->name('lc-bills.pdf');
        });

        Route::middleware('permission:lc-bills.create')->group(function () {
            Route::get('lc-bills/create', [Admin\LcBillController::class, 'create'])->name('lc-bills.create');
            Route::post('lc-bills', [Admin\LcBillController::class, 'store'])->name('lc-bills.store');
        });

        Route::middleware('permission:lc-bills.edit')->group(function () {
            Route::get('lc-bills/{lcBill}/edit', [Admin\LcBillController::class, 'edit'])->name('lc-bills.edit');
            Route::put('lc-bills/{lcBill}', [Admin\LcBillController::class, 'update'])->name('lc-bills.update');
        });

        Route::delete('lc-bills/{lcBill}', [Admin\LcBillController::class, 'destroy'])
            ->middleware('permission:lc-bills.delete')
            ->name('lc-bills.destroy');

        Route::middleware('permission:tt-accounts.view')->group(function () {
            Route::get('tt-accounts', [Admin\TtAccountController::class, 'index'])->name('tt-accounts.index');
            Route::get('tt-accounts/{ttAccount}', [Admin\TtAccountController::class, 'show'])
                ->whereNumber('ttAccount')
                ->name('tt-accounts.show');
            Route::get('tt-accounts/{ttAccount}/print', [Admin\TtAccountController::class, 'print'])
                ->whereNumber('ttAccount')
                ->name('tt-accounts.print');
            Route::get('tt-accounts/{ttAccount}/pdf', [Admin\TtAccountController::class, 'pdf'])
                ->whereNumber('ttAccount')
                ->name('tt-accounts.pdf');
        });

        Route::middleware('permission:tt-accounts.create')->group(function () {
            Route::get('tt-accounts/create', [Admin\TtAccountController::class, 'create'])->name('tt-accounts.create');
            Route::post('tt-accounts', [Admin\TtAccountController::class, 'store'])->name('tt-accounts.store');
        });

        Route::middleware('permission:tt-accounts.edit')->group(function () {
            Route::get('tt-accounts/{ttAccount}/edit', [Admin\TtAccountController::class, 'edit'])->name('tt-accounts.edit');
            Route::put('tt-accounts/{ttAccount}', [Admin\TtAccountController::class, 'update'])->name('tt-accounts.update');

            Route::post('tt-accounts/{ttAccount}/entries', [Admin\TtAccountEntryController::class, 'store'])
                ->whereNumber('ttAccount')
                ->name('tt-accounts.entries.store');
            Route::put('tt-accounts/{ttAccount}/entries/{entry}', [Admin\TtAccountEntryController::class, 'update'])
                ->scopeBindings()
                ->name('tt-accounts.entries.update');
            Route::delete('tt-accounts/{ttAccount}/entries/{entry}', [Admin\TtAccountEntryController::class, 'destroy'])
                ->scopeBindings()
                ->name('tt-accounts.entries.destroy');
        });

        Route::delete('tt-accounts/{ttAccount}', [Admin\TtAccountController::class, 'destroy'])
            ->middleware('permission:tt-accounts.delete')
            ->name('tt-accounts.destroy');

        Route::middleware('permission:transactions.view')->group(function () {
            Route::get('income-expense', [Admin\IncomeExpenseController::class, 'index'])->name('income-expense.index');
            Route::get('income-expense/report', [Admin\IncomeExpenseController::class, 'report'])->name('income-expense.report');
            Route::get('income-expense/report/print', [Admin\IncomeExpenseController::class, 'print'])->name('income-expense.report.print');
            Route::get('transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
        });

        Route::post('transactions', [Admin\TransactionController::class, 'store'])
            ->middleware('permission:transactions.create')
            ->name('transactions.store');
        Route::put('transactions/{transaction}', [Admin\TransactionController::class, 'update'])
            ->middleware('permission:transactions.edit')
            ->name('transactions.update');
        Route::delete('transactions/{transaction}', [Admin\TransactionController::class, 'destroy'])
            ->middleware('permission:transactions.delete')
            ->name('transactions.destroy');

        Route::resource('transaction-categories', Admin\TransactionCategoryController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware('permission:transaction-categories.manage');
    });

Route::middleware(['auth', 'approved', 'customer'])
    ->prefix('portal')
    ->as('portal.')
    ->group(function () {
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('consignments', [Portal\ConsignmentController::class, 'index'])->name('consignments.index');
        Route::get('consignments/{consignment}', [Portal\ConsignmentController::class, 'show'])->name('consignments.show');
    });
