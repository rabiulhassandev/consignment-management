<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_create_user_with_role(): void
    {
        $admin = $this->createStaffUser('users.manage');
        $role = Role::findOrCreate('Manager');
        $role->givePermissionTo('customers.view');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staff',
            'email' => 'staff@bnoorgroup.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Manager'],
            'permissions' => ['suppliers.view'],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'staff@bnoorgroup.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isStaff());
        $this->assertTrue($user->hasRole('Manager'));
        $this->assertTrue($user->can('customers.view'));
        $this->assertTrue($user->can('suppliers.view'));
        $this->assertFalse($user->can('users.manage'));
    }

    public function test_staff_with_permission_can_create_role_with_permissions(): void
    {
        $admin = $this->createStaffUser('roles.manage');

        $response = $this->actingAs($admin)->post(route('admin.roles.store'), [
            'name' => 'Accountant',
            'permissions' => ['consignments.view', 'currencies.manage'],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Accountant');
        $this->assertTrue($role->hasPermissionTo('consignments.view'));
        $this->assertTrue($role->hasPermissionTo('currencies.manage'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = Role::findByName(RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE);

        $this->actingAs($admin)->delete(route('admin.roles.destroy', $superAdminRole));

        $this->assertModelExists($superAdminRole);
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $admin = $this->createStaffUser('users.manage');

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $this->assertModelExists($admin);
    }

    public function test_staff_without_permission_cannot_manage_users(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_everything(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.customers.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
    }
}
