<?php

namespace Tests\Feature\Admin;

use App\Models\PurchaseItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_finds_item_by_sample_number(): void
    {
        $staff = $this->createStaffUser('consignments.view');
        $item = PurchaseItem::factory()->create(['sample_number' => 'SMP-UNIQUE-42']);

        $this->actingAs($staff)
            ->get(route('admin.search', ['q' => 'SMP-UNIQUE-42']))
            ->assertOk()
            ->assertSee('SMP-UNIQUE-42')
            ->assertSee($item->supplier->name)
            ->assertSee($item->consignment->customer->name)
            ->assertSee($item->consignment->consignment_no);
    }

    public function test_search_finds_item_by_own_sample_number(): void
    {
        $staff = $this->createStaffUser('consignments.view');
        $item = PurchaseItem::factory()->create(['own_sample_number' => 'OWN-UNIQUE-77']);

        $this->actingAs($staff)
            ->get(route('admin.search', ['q' => 'OWN-UNIQUE-77']))
            ->assertOk()
            ->assertSee('OWN-UNIQUE-77')
            ->assertSee($item->consignment->customer->name);
    }

    public function test_search_finds_consignment_by_number(): void
    {
        $staff = $this->createStaffUser('consignments.view');
        $item = PurchaseItem::factory()->create();
        $consignment = $item->consignment;

        $this->actingAs($staff)
            ->get(route('admin.search', ['q' => $consignment->consignment_no]))
            ->assertOk()
            ->assertSee($consignment->consignment_no)
            ->assertSee($consignment->customer->name);
    }

    public function test_search_requires_consignment_view_permission(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.search', ['q' => 'anything']))
            ->assertForbidden();
    }
}
