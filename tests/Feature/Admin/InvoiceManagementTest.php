<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => null,
            'description' => 'Papermaking towel felt',
            'quantity' => '167.5',
            'rate' => '760',
            'amount' => '127300',
        ], $overrides);
    }

    public function test_staff_can_create_invoice_with_items(): void
    {
        $staff = $this->createStaffUser('invoices.create', 'invoices.view');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'invoice_no' => 'INV-1001',
            'bill_to' => 'MIL',
            'bill_to_address' => 'House 12, Gulshan, Dhaka, Bangladesh',
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [
                $this->itemPayload(),
                $this->itemPayload(['description' => 'Delivery fee', 'quantity' => null, 'rate' => null, 'amount' => '800']),
            ],
        ]);

        $invoice = Invoice::query()->where('invoice_no', 'INV-1001')->first();

        $this->assertNotNull($invoice);
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('MIL', $invoice->bill_to);
        $this->assertSame('House 12, Gulshan, Dhaka, Bangladesh', $invoice->bill_to_address);
        $this->assertCount(2, $invoice->items);

        $deliveryFee = $invoice->items->firstWhere('description', 'Delivery fee');
        $this->assertNull($deliveryFee->quantity);
        $this->assertNull($deliveryFee->rate);
        $this->assertSame('800.00', $deliveryFee->amount);
    }

    public function test_invoice_requires_at_least_one_item(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'invoice_no' => 'INV-1001',
            'bill_to' => 'MIL',
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Invoice::count());
    }

    public function test_item_amount_is_required(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'invoice_no' => 'INV-1001',
            'bill_to' => 'MIL',
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [$this->itemPayload(['amount' => null])],
        ]);

        $response->assertSessionHasErrors('items.0.amount');
        $this->assertSame(0, Invoice::count());
    }

    public function test_invoice_number_must_be_unique(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $existing = Invoice::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'invoice_no' => $existing->invoice_no,
            'bill_to' => 'MIL',
            'invoice_date' => '2026-07-02',
            'currency_id' => $existing->currency_id,
            'items' => [$this->itemPayload()],
        ]);

        $response->assertSessionHasErrors('invoice_no');
    }

    public function test_updating_invoice_syncs_items_and_order(): void
    {
        $staff = $this->createStaffUser('invoices.edit', 'invoices.view');
        $invoice = Invoice::factory()->create();

        $kept = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 0]);
        $removed = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 1]);

        $response = $this->actingAs($staff)->put(route('admin.invoices.update', $invoice), [
            'invoice_no' => $invoice->invoice_no,
            'bill_to' => 'Updated client',
            'invoice_date' => '2026-07-05',
            'currency_id' => $invoice->currency_id,
            'items' => [
                $this->itemPayload(['description' => 'New first item']),
                $this->itemPayload(['id' => $kept->id, 'description' => 'Kept item moved last']),
            ],
        ]);

        $response->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertModelMissing($removed);
        $this->assertSame('Kept item moved last', $kept->refresh()->description);

        $items = $invoice->refresh()->items;
        $this->assertCount(2, $items);
        $this->assertSame(
            ['New first item', 'Kept item moved last'],
            $items->pluck('description')->all(),
        );
    }

    public function test_deleting_invoice_removes_its_items(): void
    {
        $staff = $this->createStaffUser('invoices.delete');
        $item = InvoiceItem::factory()->create();
        $invoice = $item->invoice;

        $this->actingAs($staff)->delete(route('admin.invoices.destroy', $invoice));

        $this->assertModelMissing($invoice);
        $this->assertModelMissing($item);
    }

    public function test_invoice_show_page_renders_items_and_total(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $item = InvoiceItem::factory()->create(['amount' => '250.00']);

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $item->invoice))
            ->assertOk()
            ->assertSee($item->invoice->invoice_no)
            ->assertSee($item->description)
            ->assertSee(number_format(250.00, 2));
    }

    public function test_invoice_print_page_renders(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = Invoice::factory()->create(['bill_to_address' => 'House 12, Gulshan, Dhaka, Bangladesh']);
        $item = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_no)
            ->assertSee($invoice->bill_to)
            ->assertSee('House 12, Gulshan, Dhaka, Bangladesh')
            ->assertSee($item->description);
    }

    public function test_invoice_print_page_renders_billing_document_settings(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        Setting::set('company_registration_no', 'BIN 004561234-0101');
        Setting::set('bank_swift_code', 'CIBLBDDH');
        Setting::set('bank_routing_number', '225264535');
        Setting::set('invoice_payment_terms', 'Payment due within 15 days of invoice date.');
        Setting::set('invoice_terms', 'Goods once sold are not returnable.');
        Setting::set('invoice_signatory_name', 'Mahbub Rahman');
        Setting::set('invoice_signatory_designation', 'Managing Director');

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('BIN 004561234-0101')
            ->assertSee('CIBLBDDH')
            ->assertSee('225264535')
            ->assertSee('Payment due within 15 days of invoice date.')
            ->assertSee('Goods once sold are not returnable.')
            ->assertSee('Mahbub Rahman')
            ->assertSee('Managing Director')
            ->assertDontSee('Authorized Signature');
    }

    public function test_invoice_print_page_omits_unset_billing_document_settings(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Authorized Signature')
            ->assertDontSee('Terms &amp; Conditions')
            ->assertDontSee('SWIFT / BIC')
            ->assertDontSee('Reg. No.');
    }

    public function test_staff_without_permission_cannot_create_invoice(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.invoices.create'))
            ->assertForbidden();
    }

    public function test_staff_without_permission_cannot_view_invoices(): void
    {
        $staff = $this->createStaffUser();
        $invoice = Invoice::factory()->create();

        $this->actingAs($staff)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.invoices.print', $invoice))->assertForbidden();
    }
}
