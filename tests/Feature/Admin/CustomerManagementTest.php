<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
