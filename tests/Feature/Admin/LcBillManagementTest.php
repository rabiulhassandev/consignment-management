<?php

namespace Tests\Feature\Admin;

use App\Enums\ConversionOperation;
use App\Enums\EntryType;
use App\Models\Currency;
use App\Models\LcBill;
use App\Models\LcBillEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LcBillManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function billPayload(User $customer, Currency $currency, array $overrides = []): array
    {
        return array_merge([
            'bill_no' => 'LCB-2604',
            'customer_id' => $customer->id,
            'bill_date' => '2026-06-05',
            'lc_number' => '350626010291',
            'lc_value' => '9071.00',
            'ci_value' => '9071.00',
            'shipment_title' => '20 GP CONTAINER TO CHITTAGONG',
            'currency_id' => $currency->id,
            'conversion_rate' => '124',
            'conversion_currency_id' => $currency->id,
            'conversion_operation' => ConversionOperation::Multiply->value,
        ], $overrides);
    }

    private function entryPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => null,
            'entry_date' => null,
            'description' => 'Container freight',
            'source_amount' => null,
            'source_rate' => null,
            'amount' => '3350',
        ], $overrides);
    }

    public function test_staff_can_create_lc_bill_with_receipts_and_payments(): void
    {
        $staff = $this->createStaffUser('lc-bills.create', 'lc-bills.view');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $currency),
            'receipts' => [
                $this->entryPayload(['description' => 'CI value received', 'amount' => '8588.30']),
            ],
            'payments' => [
                $this->entryPayload(),
                $this->entryPayload(['description' => 'China office handling fee', 'amount' => '500']),
            ],
        ]);

        $lcBill = LcBill::query()->where('bill_no', 'LCB-2604')->first();

        $this->assertNotNull($lcBill);
        $response->assertRedirect(route('admin.lc-bills.show', $lcBill));
        $this->assertSame($customer->id, $lcBill->customer_id);
        $this->assertFalse($lcBill->is_settled);

        $entries = $lcBill->entries;
        $this->assertCount(3, $entries);
        $this->assertSame(
            [0],
            $entries->where('type', EntryType::Received)->pluck('sort_order')->all(),
        );
        $this->assertSame(
            ['Container freight', 'China office handling fee'],
            $entries->where('type', EntryType::Paid)->pluck('description')->all(),
        );
    }

    public function test_customer_must_be_a_registered_customer(): void
    {
        $staff = $this->createStaffUser('lc-bills.create');
        $otherStaff = User::factory()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($otherStaff, $currency),
            'payments' => [$this->entryPayload()],
        ]);

        $response->assertSessionHasErrors('customer_id');
        $this->assertSame(0, LcBill::count());
    }

    public function test_bill_number_must_be_unique(): void
    {
        $staff = $this->createStaffUser('lc-bills.create');
        $existing = LcBill::factory()->create();
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $existing->currency, ['bill_no' => $existing->bill_no]),
            'payments' => [$this->entryPayload()],
        ]);

        $response->assertSessionHasErrors('bill_no');
    }

    public function test_show_page_displays_totals_balance_and_due(): void
    {
        $staff = $this->createStaffUser('lc-bills.view');
        $taka = Currency::factory()->create(['code' => 'BDT', 'symbol' => '৳']);
        $lcBill = LcBill::factory()->convertedTo($taka, '124')->create();

        LcBillEntry::factory()->received()->create(['lc_bill_id' => $lcBill->id, 'amount' => '8588.30']);

        foreach (['3350', '179.10', '500', '298.51', '80', '350'] as $amount) {
            LcBillEntry::factory()->paid()->create(['lc_bill_id' => $lcBill->id, 'amount' => $amount]);
        }

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.show', $lcBill))
            ->assertOk()
            ->assertSee('8,588.30')
            ->assertSee('4,757.61')
            ->assertSee('3,830.69')
            ->assertSee('475,005.56')
            ->assertSee('Due (BDT)');
    }

    public function test_balance_is_divided_when_the_divide_operation_is_selected(): void
    {
        $staff = $this->createStaffUser('lc-bills.create', 'lc-bills.view');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();
        $settlementCurrency = Currency::factory()->create(['code' => 'CNY', 'symbol' => '¥']);

        $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $currency, [
                'conversion_rate' => '6.7',
                'conversion_currency_id' => $settlementCurrency->id,
                'conversion_operation' => ConversionOperation::Divide->value,
            ]),
            'receipts' => [$this->entryPayload(['description' => 'CI value received', 'amount' => '1000'])],
        ]);

        $lcBill = LcBill::query()->where('bill_no', 'LCB-2604')->sole();

        $this->assertSame(ConversionOperation::Divide, $lcBill->conversion_operation);
        $this->assertSame($settlementCurrency->id, $lcBill->conversion_currency_id);
        $this->assertSame(149.25, $lcBill->localDue());

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.show', $lcBill))
            ->assertOk()
            ->assertSee('Due (CNY)')
            ->assertSee('149.25');
    }

    public function test_conversion_currency_is_required_when_a_bank_rate_is_set(): void
    {
        $staff = $this->createStaffUser('lc-bills.create');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $currency, ['conversion_currency_id' => null]),
            'payments' => [$this->entryPayload()],
        ]);

        $response->assertSessionHasErrors('conversion_currency_id');
        $this->assertSame(0, LcBill::count());
    }

    public function test_bill_without_a_bank_rate_has_no_converted_due(): void
    {
        $staff = $this->createStaffUser('lc-bills.create', 'lc-bills.view');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $currency, [
                'conversion_rate' => null,
                'conversion_currency_id' => null,
            ]),
            'payments' => [$this->entryPayload()],
        ]);

        $lcBill = LcBill::query()->where('bill_no', 'LCB-2604')->sole();

        $this->assertNull($lcBill->conversion_rate);
        $this->assertNull($lcBill->load('entries')->localDue());
    }

    public function test_source_conversion_fields_are_stored(): void
    {
        $staff = $this->createStaffUser('lc-bills.create', 'lc-bills.view');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $this->actingAs($staff)->post(route('admin.lc-bills.store'), [
            ...$this->billPayload($customer, $currency),
            'payments' => [
                $this->entryPayload([
                    'description' => 'Loading cost 1200 / 6.7',
                    'source_amount' => '1200',
                    'source_rate' => '6.7',
                    'amount' => '179.10',
                ]),
            ],
        ]);

        $entry = LcBill::query()->where('bill_no', 'LCB-2604')->first()->entries->sole();

        $this->assertSame('1200.00', $entry->source_amount);
        $this->assertSame('6.7000', $entry->source_rate);
        $this->assertSame('179.10', $entry->amount);
    }

    public function test_updating_bill_syncs_entries_across_both_sides(): void
    {
        $staff = $this->createStaffUser('lc-bills.edit', 'lc-bills.view');
        $lcBill = LcBill::factory()->create();
        $kept = LcBillEntry::factory()->received()->create(['lc_bill_id' => $lcBill->id]);
        $removed = LcBillEntry::factory()->paid()->create(['lc_bill_id' => $lcBill->id]);

        $response = $this->actingAs($staff)->put(route('admin.lc-bills.update', $lcBill), [
            ...$this->billPayload($lcBill->customer, $lcBill->currency, ['bill_no' => $lcBill->bill_no, 'is_settled' => '1']),
            'receipts' => [
                $this->entryPayload(['id' => $kept->id, 'description' => 'Updated receipt', 'amount' => '9000']),
            ],
            'payments' => [
                $this->entryPayload(['description' => 'New expense', 'amount' => '120.50']),
            ],
        ]);

        $response->assertRedirect(route('admin.lc-bills.show', $lcBill));

        $this->assertModelMissing($removed);
        $this->assertSame('Updated receipt', $kept->refresh()->description);
        $this->assertSame(EntryType::Received, $kept->type);
        $this->assertTrue($lcBill->refresh()->is_settled);

        $entries = $lcBill->entries;
        $this->assertCount(2, $entries);
        $this->assertSame('New expense', $entries->firstWhere('type', EntryType::Paid)->description);
    }

    public function test_deleting_bill_removes_its_entries(): void
    {
        $staff = $this->createStaffUser('lc-bills.delete');
        $entry = LcBillEntry::factory()->create();
        $lcBill = $entry->lcBill;

        $this->actingAs($staff)->delete(route('admin.lc-bills.destroy', $lcBill));

        $this->assertModelMissing($lcBill);
        $this->assertModelMissing($entry);
    }

    public function test_print_page_renders(): void
    {
        $staff = $this->createStaffUser('lc-bills.view');
        $entry = LcBillEntry::factory()->create();
        $lcBill = $entry->lcBill;

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.print', $lcBill))
            ->assertOk()
            ->assertSee($lcBill->bill_no)
            ->assertSee($lcBill->lc_number)
            ->assertSee($entry->description);
    }

    public function test_forms_render_the_conversion_controls(): void
    {
        $staff = $this->createStaffUser('lc-bills.create', 'lc-bills.edit');
        $taka = Currency::factory()->create(['code' => 'BDT', 'symbol' => '৳']);
        $lcBill = LcBill::factory()->convertedTo($taka, '124', ConversionOperation::Divide)->create();

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.create'))
            ->assertOk()
            ->assertSee('conversion_currency_id')
            ->assertSee('conversion_operation')
            ->assertSee('Bank rate');

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.edit', $lcBill))
            ->assertOk()
            ->assertSee('value="divide" selected', false);
    }

    public function test_staff_without_permission_cannot_create_lc_bill(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.lc-bills.create'))
            ->assertForbidden();
    }

    public function test_staff_without_permission_cannot_view_lc_bills(): void
    {
        $staff = $this->createStaffUser();
        $lcBill = LcBill::factory()->create();

        $this->actingAs($staff)->get(route('admin.lc-bills.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.lc-bills.print', $lcBill))->assertForbidden();
    }
}
