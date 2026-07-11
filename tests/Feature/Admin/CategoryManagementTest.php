<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_manage_categories(): void
    {
        $staff = $this->createStaffUser('categories.manage');

        $this->actingAs($staff)->post(route('admin.categories.store'), ['name' => 'Cotton']);

        $category = Category::query()->where('name', 'Cotton')->first();
        $this->assertNotNull($category);

        $this->actingAs($staff)->put(route('admin.categories.update', $category), ['name' => 'Organic Cotton']);
        $this->assertSame('Organic Cotton', $category->refresh()->name);

        $this->actingAs($staff)->delete(route('admin.categories.destroy', $category));
        $this->assertModelMissing($category);
    }

    public function test_category_name_must_be_unique(): void
    {
        $staff = $this->createStaffUser('categories.manage');
        Category::factory()->create(['name' => 'Cotton']);

        $this->actingAs($staff)
            ->post(route('admin.categories.store'), ['name' => 'Cotton'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $staff = $this->createStaffUser('categories.manage');
        $supplier = Supplier::factory()->create();
        $category = $supplier->category;

        $this->actingAs($staff)->delete(route('admin.categories.destroy', $category));

        $this->assertModelExists($category);
    }

    public function test_staff_without_permission_cannot_manage_categories(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }
}
