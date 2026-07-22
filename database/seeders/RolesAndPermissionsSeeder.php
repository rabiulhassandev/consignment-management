<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * Permissions grouped by module.
     *
     * @var array<string, list<string>>
     */
    public const PERMISSIONS = [
        'Customers' => ['customers.view', 'customers.create', 'customers.edit', 'customers.approve'],
        'Suppliers' => ['suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete'],
        'Consignments' => ['consignments.view', 'consignments.create', 'consignments.edit', 'consignments.delete'],
        'Invoices' => ['invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete'],
        'LC Bills' => ['lc-bills.view', 'lc-bills.create', 'lc-bills.edit', 'lc-bills.delete'],
        'TT Accounts' => ['tt-accounts.view', 'tt-accounts.create', 'tt-accounts.edit', 'tt-accounts.delete'],
        'Income & Expense' => ['transactions.view', 'transactions.create', 'transactions.edit', 'transactions.delete', 'transaction-categories.manage'],
        'Categories' => ['categories.manage'],
        'Currencies' => ['currencies.manage'],
        'Settings' => ['settings.manage'],
        'Users' => ['users.manage'],
        'Roles' => ['roles.manage'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission);
            }
        }

        Role::findOrCreate(self::SUPER_ADMIN_ROLE);
    }
}
