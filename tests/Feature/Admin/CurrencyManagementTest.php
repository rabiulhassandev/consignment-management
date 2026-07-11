<?php

namespace Tests\Feature\Admin;

use App\Models\Consignment;
use App\Models\Currency;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurrencyManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staff_with_permission_can_manage_currencies(): void
    {
        $staff = $this->createStaffUser('currencies.manage');

        $this->actingAs($staff)->post(route('admin.currencies.store'), [
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'is_active' => '1',
        ]);

        $currency = Currency::query()->where('code', 'USD')->first();
        $this->assertNotNull($currency);
        $this->assertTrue($currency->is_active);

        $this->actingAs($staff)->put(route('admin.currencies.update', $currency), [
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
        ]);
        $this->assertFalse($currency->refresh()->is_active);

        $this->actingAs($staff)->delete(route('admin.currencies.destroy', $currency));
        $this->assertModelMissing($currency);
    }

    public function test_currency_code_must_be_unique(): void
    {
        $staff = $this->createStaffUser('currencies.manage');
        Currency::factory()->create(['code' => 'USD']);

        $this->actingAs($staff)
            ->post(route('admin.currencies.store'), [
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_currency_used_by_consignments_cannot_be_deleted(): void
    {
        $staff = $this->createStaffUser('currencies.manage');
        $consignment = Consignment::factory()->create();
        $currency = $consignment->currency;

        $this->actingAs($staff)->delete(route('admin.currencies.destroy', $currency));

        $this->assertModelExists($currency);
    }

    public function test_staff_without_permission_cannot_manage_currencies(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.currencies.index'))
            ->assertForbidden();
    }
}
