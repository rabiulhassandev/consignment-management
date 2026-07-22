<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_view_customer_list(): void
    {
        $staff = $this->createStaffUser('customers.view');
        $customer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_staff_without_permission_cannot_view_customer_list(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.customers.index'))
            ->assertForbidden();
    }

    public function test_staff_with_permission_can_create_a_customer_with_login_credentials(): void
    {
        $staff = $this->createStaffUser('customers.create', 'customers.view');

        $this->actingAs($staff)
            ->get(route('admin.customers.create'))
            ->assertOk()
            ->assertSee('Login Credentials');

        $response = $this->actingAs($staff)->post(route('admin.customers.store'), [
            'name' => 'Rahim Trading',
            'email' => 'rahim@example.com',
            'phone' => '01711000000',
            'company_name' => 'Rahim Trading Ltd',
            'address' => 'Dhaka, Bangladesh',
            'status' => UserStatus::Approved->value,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $customer = User::query()->where('email', 'rahim@example.com')->sole();

        $response->assertRedirect(route('admin.customers.show', $customer));
        $this->assertTrue($customer->isCustomer());
        $this->assertSame(UserStatus::Approved, $customer->status);
        $this->assertSame('Rahim Trading Ltd', $customer->company_name);
        $this->assertTrue(Hash::check('new-secret-password', $customer->password));
    }

    public function test_created_customer_can_log_in_immediately(): void
    {
        $staff = $this->createStaffUser('customers.create', 'customers.view');

        $this->actingAs($staff)->post(route('admin.customers.store'), [
            'name' => 'Rahim Trading',
            'email' => 'rahim@example.com',
            'status' => UserStatus::Approved->value,
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'rahim@example.com',
            'password' => 'new-secret-password',
        ]);

        $this->assertAuthenticatedAs(User::query()->where('email', 'rahim@example.com')->sole());
    }

    public function test_creating_a_customer_requires_a_unique_email_and_confirmed_password(): void
    {
        $staff = $this->createStaffUser('customers.create');
        $existing = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.store'), [
            'name' => 'Rahim Trading',
            'email' => $existing->email,
            'status' => UserStatus::Approved->value,
            'password' => 'new-secret-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertSame(1, User::customers()->count());
    }

    public function test_staff_without_permission_cannot_create_a_customer(): void
    {
        $staff = $this->createStaffUser('customers.view');

        $this->actingAs($staff)
            ->get(route('admin.customers.create'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('admin.customers.store'), [
                'name' => 'Rahim Trading',
                'email' => 'rahim@example.com',
                'status' => UserStatus::Approved->value,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertForbidden();

        $this->assertSame(0, User::customers()->count());
    }

    public function test_add_customer_button_is_only_shown_with_permission(): void
    {
        $this->actingAs($this->createStaffUser('customers.view'))
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee('Add Customer');

        $this->actingAs($this->createStaffUser('customers.view', 'customers.create'))
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Add Customer');
    }

    public function test_customer_cannot_access_admin_area(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_customer_profile_page_shows_stats(): void
    {
        $staff = $this->createStaffUser('customers.view');
        $customer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee($customer->email);
    }

    public function test_staff_can_update_customer_basic_info(): void
    {
        $staff = $this->createStaffUser('customers.edit');
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->put(route('admin.customers.update', $customer), [
            'name' => 'Updated Name',
            'email' => $customer->email,
            'phone' => '0987654321',
            'company_name' => 'Updated Co',
            'address' => 'New address',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));
        $this->assertSame('Updated Name', $customer->refresh()->name);
    }

    public function test_staff_with_permission_can_change_customer_password(): void
    {
        $staff = $this->createStaffUser('customers.edit');
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->patch(route('admin.customers.password.update', $customer), [
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));
        $this->assertTrue(Hash::check('new-secret-password', $customer->refresh()->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $staff = $this->createStaffUser('customers.edit');
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->patch(route('admin.customers.password.update', $customer), [
            'password' => 'new-secret-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $customer->refresh()->password));
    }

    public function test_staff_without_permission_cannot_change_customer_password(): void
    {
        $staff = $this->createStaffUser('customers.view');
        $customer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->patch(route('admin.customers.password.update', $customer), [
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $customer->refresh()->password));
    }

    public function test_password_change_route_rejects_non_customer_users(): void
    {
        $staff = $this->createStaffUser('customers.edit');
        $otherStaff = User::factory()->create();

        $this->actingAs($staff)
            ->patch(route('admin.customers.password.update', $otherStaff), [
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertNotFound();
    }

    public function test_staff_can_approve_pending_customer(): void
    {
        $staff = $this->createStaffUser('customers.approve');
        $customer = User::factory()->customer()->pending()->create();

        $this->actingAs($staff)->patch(route('admin.customers.approve', $customer));

        $this->assertSame(UserStatus::Approved, $customer->refresh()->status);
    }

    public function test_staff_can_reject_pending_customer(): void
    {
        $staff = $this->createStaffUser('customers.approve');
        $customer = User::factory()->customer()->pending()->create();

        $this->actingAs($staff)->patch(route('admin.customers.reject', $customer));

        $this->assertSame(UserStatus::Rejected, $customer->refresh()->status);
    }

    public function test_staff_without_approve_permission_cannot_approve(): void
    {
        $staff = $this->createStaffUser('customers.view');
        $customer = User::factory()->customer()->pending()->create();

        $this->actingAs($staff)
            ->patch(route('admin.customers.approve', $customer))
            ->assertForbidden();

        $this->assertSame(UserStatus::Pending, $customer->refresh()->status);
    }

    public function test_staff_profile_route_rejects_non_customer_users(): void
    {
        $staff = $this->createStaffUser('customers.view');
        $otherStaff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.show', $otherStaff))
            ->assertNotFound();
    }
}
