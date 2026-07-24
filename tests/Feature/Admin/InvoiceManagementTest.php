<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_invoice_show_page_includes_pdf_download_link(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $item = InvoiceItem::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $item->invoice))
            ->assertOk()
            ->assertSee(route('admin.invoices.pdf', $item->invoice), false);
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

    public function test_invoice_print_page_includes_share_pdf_wiring(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Share PDF')
            ->assertSee(str_replace('/', '\/', route('admin.invoices.pdf', $invoice)), false);
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

    public function test_invoice_pdf_download_is_generated(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $response = $this->actingAs($staff)->get(route('admin.invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            "invoice-{$invoice->invoice_no}.pdf",
            $response->headers->get('content-disposition'),
        );
    }

    public function test_invoice_pdf_includes_billing_document_settings(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        Setting::set('bank_swift_code', 'CIBLBDDH');
        Setting::set('bank_routing_number', '225264535');
        Setting::set('invoice_payment_terms', 'Payment due within 15 days of invoice date.');
        Setting::set('invoice_terms', 'Goods once sold are not returnable.');
        Setting::set('invoice_signatory_name', 'Mahbub Rahman');
        Setting::set('invoice_signatory_designation', 'Managing Director');

        $response = $this->actingAs($staff)->get(route('admin.invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_show_page_includes_excel_download_link(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $item = InvoiceItem::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $item->invoice))
            ->assertOk()
            ->assertSee(route('admin.invoices.excel', $item->invoice), false);
    }

    /**
     * Flatten every non-empty cell of the generated workbook into a list of string values.
     *
     * @return array<int, string>
     */
    private function excelCellValues(Invoice $invoice): array
    {
        $response = $this->get(route('admin.invoices.excel', $invoice));
        $response->assertOk();

        $tempFile = tempnam(sys_get_temp_dir(), 'invoice-excel-test-');
        file_put_contents($tempFile, $response->streamedContent());

        $sheet = IOFactory::load($tempFile)->getActiveSheet();
        $values = [];

        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();

                if ($value !== null && $value !== '') {
                    $values[] = (string) $value;
                }
            }
        }

        unlink($tempFile);

        return $values;
    }

    public function test_invoice_excel_download_is_generated(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = Invoice::factory()->create(['bill_to' => 'MIL']);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Papermaking towel felt',
            'quantity' => '167.50',
            'rate' => '760.00',
            'amount' => '127300.00',
        ]);

        $response = $this->actingAs($staff)->get(route('admin.invoices.excel', $invoice));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $this->assertStringContainsString(
            "invoice-{$invoice->invoice_no}.xlsx",
            $response->headers->get('content-disposition'),
        );
    }

    public function test_invoice_excel_contains_invoice_details_and_totals(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $currency = Currency::factory()->create(['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥']);
        $invoice = Invoice::factory()->create([
            'invoice_no' => 'INV-7788',
            'bill_to' => 'MIL',
            'bill_to_address' => 'House 12, Gulshan, Dhaka, Bangladesh',
            'currency_id' => $currency->id,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Papermaking towel felt',
            'quantity' => '167.50',
            'rate' => '760.00',
            'amount' => '127300.00',
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Delivery fee',
            'quantity' => null,
            'rate' => null,
            'amount' => '800.00',
        ]);

        $this->actingAs($staff);
        $values = $this->excelCellValues($invoice);

        $this->assertContains('INVOICE', $values);
        $this->assertContains('INV-7788', $values);
        $this->assertContains('MIL', $values);
        $this->assertContains('House 12, Gulshan, Dhaka, Bangladesh', $values);
        $this->assertContains('CNY', $values);
        $this->assertContains('Papermaking towel felt', $values);
        $this->assertContains('Delivery fee', $values);
        $this->assertContains('Amount (CNY)', $values);
        $this->assertContains('Amount in Chinese Yuan (CNY) only', $values);
        $this->assertContains('TOTAL', $values);
        $this->assertContains('128100', $values);
        $this->assertContains('127300', $values);
        $this->assertContains('800', $values);
        $this->assertContains('—', $values, 'Null quantity and rate should render as an em dash.');
    }

    public function test_invoice_excel_includes_company_letterhead_and_billing_settings(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        Setting::set('company_name', 'BNoor Global Trading');
        Setting::set('company_tagline', 'Global Sourcing & Freight Forwarding');
        Setting::set('company_registration_no', 'BIN 004561234-0101');
        Setting::set('company_website', 'www.bnoorgroup.com');
        Setting::set('site_email', 'bnoor00@hotmail.com');
        Setting::set('dhaka_office_address', 'House 14, Road 12, Uttara, Dhaka');
        Setting::set('bank_name', 'The City Bank Limited');
        Setting::set('bank_account_number', '1504311841001');
        Setting::set('bank_swift_code', 'CIBLBDDH');
        Setting::set('bank_routing_number', '225264535');
        Setting::set('invoice_payment_terms', 'Payment due within 15 days of invoice date.');
        Setting::set('invoice_terms', 'Goods once sold are not returnable.');
        Setting::set('invoice_signatory_name', 'Mahbub Rahman');
        Setting::set('invoice_signatory_designation', 'Managing Director');
        Setting::set('invoice_footer_note', 'Thank you for your business.');

        $this->actingAs($staff);
        $values = $this->excelCellValues($invoice);

        $this->assertContains('BNOOR GLOBAL TRADING', $values);
        $this->assertContains('Global Sourcing & Freight Forwarding', $values);
        $this->assertContains('Reg. No. BIN 004561234-0101', $values);
        $this->assertContains('PAYMENT DETAILS', $values);
        $this->assertContains('The City Bank Limited', $values);
        $this->assertContains('1504311841001', $values);
        $this->assertContains('CIBLBDDH', $values);
        $this->assertContains('225264535', $values);
        $this->assertContains('Payment due within 15 days of invoice date.', $values);
        $this->assertContains('TERMS & CONDITIONS', $values);
        $this->assertContains('Goods once sold are not returnable.', $values);
        $this->assertContains('Mahbub Rahman', $values);
        $this->assertContains('Managing Director', $values);
        $this->assertContains('For BNoor Global Trading', $values);
        $this->assertContains('Thank you for your business.', $values);
        $this->assertContains('Dhaka Office: House 14, Road 12, Uttara, Dhaka', $values);
        $this->assertContains('www.bnoorgroup.com    bnoor00@hotmail.com', $values);
    }

    public function test_invoice_excel_omits_unset_billing_document_settings(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff);
        $values = $this->excelCellValues($invoice);

        $this->assertContains('Authorized Signature', $values);
        $this->assertNotContains('TERMS & CONDITIONS', $values);
        $this->assertNotContains('PAYMENT DETAILS', $values);
        $this->assertNotContains('SWIFT / BIC', $values);
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
        $this->actingAs($staff)->get(route('admin.invoices.pdf', $invoice))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.invoices.excel', $invoice))->assertForbidden();
    }
}
