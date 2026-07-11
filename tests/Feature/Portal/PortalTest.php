<?php

namespace Tests\Feature\Portal;

use App\Models\Consignment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_view_portal_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_customer_sees_only_their_own_consignments(): void
    {
        $customer = User::factory()->customer()->create();
        $own = Consignment::factory()->for($customer, 'customer')->create();
        $foreign = Consignment::factory()->create();

        $this->actingAs($customer)
            ->get(route('portal.consignments.index'))
            ->assertOk()
            ->assertSee($own->consignment_no)
            ->assertDontSee($foreign->consignment_no);
    }

    public function test_customer_can_view_own_consignment_details(): void
    {
        $customer = User::factory()->customer()->create();
        $consignment = Consignment::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->get(route('portal.consignments.show', $consignment))
            ->assertOk()
            ->assertSee($consignment->consignment_no);
    }

    public function test_customer_cannot_view_another_customers_consignment(): void
    {
        $customer = User::factory()->customer()->create();
        $foreign = Consignment::factory()->create();

        $this->actingAs($customer)
            ->get(route('portal.consignments.show', $foreign))
            ->assertNotFound();
    }

    public function test_staff_cannot_access_portal(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
    }
}
