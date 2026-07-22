<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\SalesContract;
use App\Models\SalesContractItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SalesContractManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => null,
            'description' => 'Bag Accessories',
            'hs_code' => '4202.92.00',
            'quantity' => '600',
            'unit' => 'SETS',
            'unit_price' => '42.00',
            'amount' => '25200',
        ], $overrides);
    }

    public function test_staff_can_create_sales_contract_with_items_and_freight(): void
    {
        $staff = $this->createStaffUser('sales-contracts.create', 'sales-contracts.view');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.sales-contracts.store'), [
            'contract_no' => 'BN000061',
            'buyer' => 'MIL Trading',
            'buyer_address' => 'Dhaka, Bangladesh',
            'contract_date' => '2026-08-10',
            'currency_id' => $currency->id,
            'freight_charge' => '757',
            'terms' => "THE PRICE IS BASED ON EXW\n100% RMB TT IN ADVANCE",
            'items' => [
                $this->itemPayload(),
                $this->itemPayload([
                    'description' => 'Sample charge',
                    'hs_code' => null,
                    'quantity' => null,
                    'unit' => null,
                    'unit_price' => null,
                    'amount' => '0',
                ]),
            ],
        ]);

        $salesContract = SalesContract::query()->where('contract_no', 'BN000061')->first();

        $this->assertNotNull($salesContract);
        $response->assertRedirect(route('admin.sales-contracts.show', $salesContract));
        $this->assertSame('MIL Trading', $salesContract->buyer);
        $this->assertSame('Dhaka, Bangladesh', $salesContract->buyer_address);
        $this->assertSame('757.00', $salesContract->freight_charge);
        $this->assertCount(2, $salesContract->items);

        $firstItem = $salesContract->items->first();
        $this->assertSame('4202.92.00', $firstItem->hs_code);
        $this->assertSame('SETS', $firstItem->unit);
        $this->assertSame('42.00', $firstItem->unit_price);

        $sampleCharge = $salesContract->items->firstWhere('description', 'Sample charge');
        $this->assertNull($sampleCharge->hs_code);
        $this->assertNull($sampleCharge->quantity);
        $this->assertNull($sampleCharge->unit);
        $this->assertNull($sampleCharge->unit_price);
    }

    public function test_total_amount_adds_freight_to_the_line_items(): void
    {
        $salesContract = SalesContract::factory()->create(['freight_charge' => '757.00']);
        SalesContractItem::factory()->create(['sales_contract_id' => $salesContract->id, 'amount' => '25200.00']);

        $salesContract->load('items');

        $this->assertSame(25200.0, $salesContract->itemsTotal());
        $this->assertSame(25957.0, $salesContract->totalAmount());
    }

    public function test_amount_in_words_spells_the_contract_total(): void
    {
        $currency = Currency::factory()->create(['name' => 'Yuan', 'code' => 'CNY']);
        $salesContract = SalesContract::factory()->create([
            'currency_id' => $currency->id,
            'freight_charge' => '757.00',
        ]);
        SalesContractItem::factory()->create(['sales_contract_id' => $salesContract->id, 'amount' => '25200.00']);

        $salesContract->load(['currency', 'items']);

        $this->assertSame(
            'Twenty Five Thousand Nine Hundred Fifty Seven Yuan Only',
            $salesContract->amountInWords(),
        );
    }

    public function test_amount_in_words_includes_a_fractional_remainder(): void
    {
        $currency = Currency::factory()->create(['name' => 'Yuan', 'code' => 'CNY']);
        $salesContract = SalesContract::factory()->withoutFreight()->create(['currency_id' => $currency->id]);
        SalesContractItem::factory()->create(['sales_contract_id' => $salesContract->id, 'amount' => '120.50']);

        $salesContract->load(['currency', 'items']);

        $this->assertSame('One Hundred Twenty and 50/100 Yuan Only', $salesContract->amountInWords());
    }

    public function test_sales_contract_requires_at_least_one_item(): void
    {
        $staff = $this->createStaffUser('sales-contracts.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.sales-contracts.store'), [
            'contract_no' => 'SC-1001',
            'buyer' => 'MIL Trading',
            'contract_date' => '2026-08-10',
            'currency_id' => $currency->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, SalesContract::count());
    }

    public function test_item_amount_is_required(): void
    {
        $staff = $this->createStaffUser('sales-contracts.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.sales-contracts.store'), [
            'contract_no' => 'SC-1001',
            'buyer' => 'MIL Trading',
            'contract_date' => '2026-08-10',
            'currency_id' => $currency->id,
            'items' => [$this->itemPayload(['amount' => null])],
        ]);

        $response->assertSessionHasErrors('items.0.amount');
        $this->assertSame(0, SalesContract::count());
    }

    public function test_contract_number_must_be_unique(): void
    {
        $staff = $this->createStaffUser('sales-contracts.create');
        $existing = SalesContract::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.sales-contracts.store'), [
            'contract_no' => $existing->contract_no,
            'buyer' => 'MIL Trading',
            'contract_date' => '2026-08-10',
            'currency_id' => $existing->currency_id,
            'items' => [$this->itemPayload()],
        ]);

        $response->assertSessionHasErrors('contract_no');
    }

    public function test_updating_sales_contract_syncs_items_and_order(): void
    {
        $staff = $this->createStaffUser('sales-contracts.edit', 'sales-contracts.view');
        $salesContract = SalesContract::factory()->create();

        $kept = SalesContractItem::factory()->create(['sales_contract_id' => $salesContract->id, 'sort_order' => 0]);
        $removed = SalesContractItem::factory()->create(['sales_contract_id' => $salesContract->id, 'sort_order' => 1]);

        $response = $this->actingAs($staff)->put(route('admin.sales-contracts.update', $salesContract), [
            'contract_no' => $salesContract->contract_no,
            'buyer' => 'Updated buyer',
            'contract_date' => '2026-08-12',
            'currency_id' => $salesContract->currency_id,
            'freight_charge' => null,
            'items' => [
                $this->itemPayload(['description' => 'New first item']),
                $this->itemPayload(['id' => $kept->id, 'description' => 'Kept item moved last']),
            ],
        ]);

        $response->assertRedirect(route('admin.sales-contracts.show', $salesContract));

        $this->assertModelMissing($removed);
        $this->assertSame('Kept item moved last', $kept->refresh()->description);
        $this->assertNull($salesContract->refresh()->freight_charge);

        $this->assertSame(
            ['New first item', 'Kept item moved last'],
            $salesContract->items->pluck('description')->all(),
        );
    }

    public function test_deleting_sales_contract_removes_its_items(): void
    {
        $staff = $this->createStaffUser('sales-contracts.delete');
        $item = SalesContractItem::factory()->create();
        $salesContract = $item->salesContract;

        $this->actingAs($staff)->delete(route('admin.sales-contracts.destroy', $salesContract));

        $this->assertModelMissing($salesContract);
        $this->assertModelMissing($item);
    }

    public function test_show_page_renders_items_totals_and_terms(): void
    {
        $staff = $this->createStaffUser('sales-contracts.view');
        $salesContract = SalesContract::factory()->create([
            'freight_charge' => '757.00',
            'terms' => "THE PRICE IS BASED ON EXW\n100% RMB TT IN ADVANCE",
        ]);
        $item = SalesContractItem::factory()->create([
            'sales_contract_id' => $salesContract->id,
            'amount' => '25200.00',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.sales-contracts.show', $salesContract))
            ->assertOk()
            ->assertSee($salesContract->contract_no)
            ->assertSee($item->description)
            ->assertSee(number_format(25957.00, 2))
            ->assertSee('THE PRICE IS BASED ON EXW');
    }

    public function test_print_page_renders_the_contract_document(): void
    {
        $staff = $this->createStaffUser('sales-contracts.view');
        $currency = Currency::factory()->create(['name' => 'Yuan', 'code' => 'CNY']);
        $salesContract = SalesContract::factory()->create([
            'currency_id' => $currency->id,
            'buyer_address' => 'Guangzhou, China',
            'freight_charge' => '757.00',
            'terms' => 'INSURANCE COVERED BY THE BUYER',
        ]);
        $item = SalesContractItem::factory()->create([
            'sales_contract_id' => $salesContract->id,
            'hs_code' => '4202.92.00',
            'unit' => 'SETS',
            'amount' => '25200.00',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.sales-contracts.print', $salesContract))
            ->assertOk()
            ->assertSee($salesContract->contract_no)
            ->assertSee($salesContract->buyer)
            ->assertSee('Guangzhou, China')
            ->assertSee($item->description)
            ->assertSee('4202.92.00')
            ->assertSee('SETS')
            ->assertSee('Freight Charge')
            ->assertSee('Twenty Five Thousand Nine Hundred Fifty Seven Yuan Only')
            ->assertSee('INSURANCE COVERED BY THE BUYER')
            ->assertSee('Seller Confirmation')
            ->assertSee('Buyer Confirmation');
    }

    public function test_pdf_download_is_generated(): void
    {
        $staff = $this->createStaffUser('sales-contracts.view');
        $salesContract = SalesContractItem::factory()->create()->salesContract;

        $response = $this->actingAs($staff)->get(route('admin.sales-contracts.pdf', $salesContract));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            "sales-contract-{$salesContract->contract_no}.pdf",
            $response->headers->get('content-disposition'),
        );
    }

    public function test_create_form_prefills_the_default_terms_setting(): void
    {
        $staff = $this->createStaffUser('sales-contracts.create');
        Currency::factory()->create();

        Setting::set('sales_contract_terms', "THE PRICE IS BASED ON EXW\n10% MORE OR LESS IS ALLOWED");

        $this->actingAs($staff)
            ->get(route('admin.sales-contracts.create'))
            ->assertOk()
            ->assertSee('THE PRICE IS BASED ON EXW')
            ->assertSee('10% MORE OR LESS IS ALLOWED');
    }

    public function test_staff_without_permission_cannot_create_sales_contract(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.sales-contracts.create'))
            ->assertForbidden();
    }

    public function test_staff_without_permission_cannot_view_sales_contracts(): void
    {
        $staff = $this->createStaffUser();
        $salesContract = SalesContract::factory()->create();

        $this->actingAs($staff)->get(route('admin.sales-contracts.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.sales-contracts.print', $salesContract))->assertForbidden();
    }
}
