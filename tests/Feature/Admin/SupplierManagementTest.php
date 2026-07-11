<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_can_add_supplier_for_customer(): void
    {
        $staff = $this->createStaffUser('suppliers.create');
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.suppliers.store', $customer), [
            'name' => 'Shanghai Fabrics Co',
            'category_id' => $category->id,
            'contact_person' => 'Mr. Li',
            'phone' => '13800000000',
            'wechat' => 'li-fabrics',
            'address' => 'Shanghai',
            'note' => 'Reliable supplier',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));

        $supplier = $customer->suppliers()->first();
        $this->assertNotNull($supplier);
        $this->assertSame('Shanghai Fabrics Co', $supplier->name);
        $this->assertSame($category->id, $supplier->category_id);
    }

    public function test_supplier_requires_category(): void
    {
        $staff = $this->createStaffUser('suppliers.create');
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->post(route('admin.customers.suppliers.store', $customer), [
            'name' => 'Shanghai Fabrics Co',
        ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_staff_can_update_supplier(): void
    {
        $staff = $this->createStaffUser('suppliers.edit');
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($staff)->put(
            route('admin.customers.suppliers.update', [$supplier->customer, $supplier]),
            [
                'name' => 'Renamed Supplier',
                'category_id' => $supplier->category_id,
            ],
        );

        $response->assertRedirect(route('admin.customers.show', $supplier->customer));
        $this->assertSame('Renamed Supplier', $supplier->refresh()->name);
    }

    public function test_supplier_update_is_scoped_to_its_customer(): void
    {
        $staff = $this->createStaffUser('suppliers.edit');
        $supplier = Supplier::factory()->create();
        $otherCustomer = User::factory()->customer()->create();

        $this->actingAs($staff)
            ->put(route('admin.customers.suppliers.update', [$otherCustomer, $supplier]), [
                'name' => 'Hijacked',
                'category_id' => $supplier->category_id,
            ])
            ->assertNotFound();
    }

    public function test_staff_can_delete_unused_supplier(): void
    {
        $staff = $this->createStaffUser('suppliers.delete');
        $supplier = Supplier::factory()->create();

        $this->actingAs($staff)->delete(
            route('admin.customers.suppliers.destroy', [$supplier->customer, $supplier]),
        );

        $this->assertModelMissing($supplier);
    }

    public function test_supplier_used_in_purchase_items_cannot_be_deleted(): void
    {
        $staff = $this->createStaffUser('suppliers.delete');
        $item = PurchaseItem::factory()->create();
        $supplier = $item->supplier;

        $this->actingAs($staff)->delete(
            route('admin.customers.suppliers.destroy', [$supplier->customer, $supplier]),
        );

        $this->assertModelExists($supplier);
    }

    public function test_staff_without_permission_cannot_add_supplier(): void
    {
        $staff = $this->createStaffUser();
        $customer = User::factory()->customer()->create();
        $category = Category::factory()->create();

        $this->actingAs($staff)
            ->post(route('admin.customers.suppliers.store', $customer), [
                'name' => 'Nope',
                'category_id' => $category->id,
            ])
            ->assertForbidden();
    }
}
