<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a staff user holding the given permissions.
     */
    protected function createStaffUser(string ...$permissions): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * Create a staff user with the Super Admin role.
     */
    protected function createSuperAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE);

        return $user;
    }
}
