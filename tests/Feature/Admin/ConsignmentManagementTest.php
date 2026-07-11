<?php

namespace Tests\Feature\Admin;

use App\Models\Consignment;
use App\Models\Currency;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConsignmentManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function itemPayload(Supplier $supplier, array $overrides = []): array
    {
        return array_merge([
            'id' => null,
            'purchase_date' => '2026-07-01',
            'product_name' => 'Cotton twill fabric',
            'category_id' => $supplier->category_id,
            'supplier_id' => $supplier->id,
            'sample_number' => 'SMP-001',
            'own_sample_number' => 'OWN-001',
            'amount' => '1500.50',
        ], $overrides);
    }

    public function test_staff_can_create_consignment_with_items(): void
    {
        $staff = $this->createStaffUser('consignments.create', 'consignments.view');
        $customer = User::factory()->customer()->create();
        $supplier = Supplier::factory()->for($customer, 'customer')->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.consignments.store', $customer), [
            'consignment_no' => 'CN-1001',
            'consignment_date' => '2026-07-10',
            'currency_id' => $currency->id,
            'items' => [
                $this->itemPayload($supplier),
                $this->itemPayload($supplier, ['sample_number' => 'SMP-002', 'own_sample_number' => 'OWN-002', 'amount' => '99.99']),
            ],
        ]);

        $consignment = Consignment::query()->where('consignment_no', 'CN-1001')->first();

        $this->assertNotNull($consignment);
        $response->assertRedirect(route('admin.consignments.show', $consignment));
        $this->assertSame($customer->id, $consignment->customer_id);
        $this->assertCount(2, $consignment->items);
    }

    public function test_consignment_requires_at_least_one_item(): void
    {
        $staff = $this->createStaffUser('consignments.create');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.consignments.store', $customer), [
            'consignment_no' => 'CN-1001',
            'consignment_date' => '2026-07-10',
            'currency_id' => $currency->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Consignment::count());
    }

    public function test_consignment_number_must_be_unique(): void
    {
        $staff = $this->createStaffUser('consignments.create');
        $existing = Consignment::factory()->create();
        $customer = User::factory()->customer()->create();
        $supplier = Supplier::factory()->for($customer, 'customer')->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.consignments.store', $customer), [
            'consignment_no' => $existing->consignment_no,
            'consignment_date' => '2026-07-10',
            'currency_id' => $existing->currency_id,
            'items' => [$this->itemPayload($supplier)],
        ]);

        $response->assertSessionHasErrors('consignment_no');
    }

    public function test_item_supplier_must_belong_to_the_customer(): void
    {
        $staff = $this->createStaffUser('consignments.create');
        $customer = User::factory()->customer()->create();
        $foreignSupplier = Supplier::factory()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.consignments.store', $customer), [
            'consignment_no' => 'CN-1001',
            'consignment_date' => '2026-07-10',
            'currency_id' => $currency->id,
            'items' => [$this->itemPayload($foreignSupplier)],
        ]);

        $response->assertSessionHasErrors('items.0.supplier_id');
    }

    public function test_updating_consignment_syncs_items(): void
    {
        $staff = $this->createStaffUser('consignments.edit', 'consignments.view');
        $consignment = Consignment::factory()->create();
        $supplier = Supplier::factory()->for($consignment->customer, 'customer')->create();

        $kept = PurchaseItem::factory()->create([
            'consignment_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $supplier->category_id,
        ]);
        $removed = PurchaseItem::factory()->create([
            'consignment_id' => $consignment->id,
            'supplier_id' => $supplier->id,
            'category_id' => $supplier->category_id,
        ]);

        $response = $this->actingAs($staff)->put(route('admin.consignments.update', $consignment), [
            'consignment_no' => $consignment->consignment_no,
            'consignment_date' => '2026-07-11',
            'currency_id' => $consignment->currency_id,
            'items' => [
                $this->itemPayload($supplier, ['id' => $kept->id, 'product_name' => 'Updated product']),
                $this->itemPayload($supplier, ['sample_number' => 'SMP-NEW', 'own_sample_number' => 'OWN-NEW']),
            ],
        ]);

        $response->assertRedirect(route('admin.consignments.show', $consignment));

        $this->assertModelMissing($removed);
        $this->assertSame('Updated product', $kept->refresh()->product_name);
        $this->assertCount(2, $consignment->refresh()->items);
    }

    public function test_deleting_consignment_removes_its_items(): void
    {
        $staff = $this->createStaffUser('consignments.delete');
        $item = PurchaseItem::factory()->create();
        $consignment = $item->consignment;

        $this->actingAs($staff)->delete(route('admin.consignments.destroy', $consignment));

        $this->assertModelMissing($consignment);
        $this->assertModelMissing($item);
    }

    public function test_consignment_show_page_renders_items_and_total(): void
    {
        $staff = $this->createStaffUser('consignments.view');
        $item = PurchaseItem::factory()->create(['amount' => '250.00']);

        $this->actingAs($staff)
            ->get(route('admin.consignments.show', $item->consignment))
            ->assertOk()
            ->assertSee($item->consignment->consignment_no)
            ->assertSee($item->product_name);
    }

    public function test_staff_without_permission_cannot_create_consignment(): void
    {
        $staff = $this->createStaffUser();
        $customer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->get(route('admin.customers.consignments.create', $customer))
            ->assertForbidden();
    }
}
