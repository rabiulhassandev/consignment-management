<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransactionCategoryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_manage_categories(): void
    {
        $staff = $this->createStaffUser('transaction-categories.manage');

        $this->actingAs($staff)->post(route('admin.transaction-categories.store'), ['type' => 'income', 'name' => 'Sales']);
        $this->actingAs($staff)->post(route('admin.transaction-categories.store'), ['type' => 'expense', 'name' => 'Rent']);

        $this->assertDatabaseHas('transaction_categories', ['type' => 'income', 'name' => 'Sales']);
        $this->assertDatabaseHas('transaction_categories', ['type' => 'expense', 'name' => 'Rent']);

        $category = TransactionCategory::query()->where('name', 'Sales')->first();

        $this->actingAs($staff)->put(route('admin.transaction-categories.update', $category), ['name' => 'Product Sales']);
        $this->assertSame('Product Sales', $category->refresh()->name);

        $this->actingAs($staff)->delete(route('admin.transaction-categories.destroy', $category));
        $this->assertModelMissing($category);
    }

    public function test_category_name_unique_per_type_but_reusable_across_types(): void
    {
        $staff = $this->createStaffUser('transaction-categories.manage');
        TransactionCategory::factory()->income()->create(['name' => 'Other']);

        $this->actingAs($staff)
            ->post(route('admin.transaction-categories.store'), ['type' => 'income', 'name' => 'Other'])
            ->assertSessionHasErrors('name');

        $this->actingAs($staff)
            ->post(route('admin.transaction-categories.store'), ['type' => 'expense', 'name' => 'Other'])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('transaction_categories', ['type' => 'expense', 'name' => 'Other']);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $staff = $this->createStaffUser('transaction-categories.manage');
        $transaction = Transaction::factory()->create();
        $category = $transaction->category;

        $this->actingAs($staff)
            ->delete(route('admin.transaction-categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertModelExists($category);
    }

    public function test_index_shows_both_category_lists(): void
    {
        $staff = $this->createStaffUser('transaction-categories.manage');
        TransactionCategory::factory()->income()->create(['name' => 'Consulting Income']);
        TransactionCategory::factory()->expense()->create(['name' => 'Warehouse Rent']);

        $this->actingAs($staff)
            ->get(route('admin.transaction-categories.index'))
            ->assertOk()
            ->assertSee('Consulting Income')
            ->assertSee('Warehouse Rent');
    }

    public function test_staff_without_permission_cannot_manage_categories(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)->get(route('admin.transaction-categories.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.transaction-categories.store'), ['type' => 'income', 'name' => 'Sales'])->assertForbidden();
    }
}
