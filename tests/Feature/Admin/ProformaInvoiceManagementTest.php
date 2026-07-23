<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProformaInvoiceManagementTest extends TestCase
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
            'description' => 'Polyester fabric',
            'hs_code' => '5407.61.00',
            'quantity' => '500',
            'unit' => 'YDS',
            'rate' => '1.85',
            'amount' => '925',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function invoicePayload(Currency $currency, array $overrides = []): array
    {
        return array_merge([
            'invoice_no' => 'AMEEN/NST/01/2023',
            'invoice_date' => '2026-11-13',
            'currency_id' => $currency->id,
            'exporter_name' => 'AMEEN HK TRADING CO.,LIMITED',
            'exporter_address' => 'UNIT D, 16/F ONE CAPITAL PLACE, WANCHAI, HONG KONG',
            'buyer_name' => 'NEW SPREADING TRADE (HK) LIMITED',
            'buyer_address' => 'Dhaka, Bangladesh',
            'advising_bank_name' => 'HSBC BANK(CHINA) COMPANY LIMITED',
            'advising_bank_address' => '27F TAIKOOHUI TOWER 2, GUANGZHOU, CHINA',
            'advising_bank_swift' => 'HSBCCNSHGZH',
            'beneficiary_name' => 'AMEEN HK TRADING CO LIMITED',
            'beneficiary_account' => '009-381559-055(USD)',
            'pre_carriage' => 'By Sea/ By Air',
            'place_of_receipt' => 'HongKong / China',
            'country_of_origin' => 'China',
            'port_of_loading' => 'Any port of China/HongKong',
            'port_of_discharge' => 'CHATTOGRAM, BANGLADESH',
            'final_destination' => 'ICD KAMALAPUR, DHAKA, BANGLADESH',
            'delivery_payment_terms' => 'BY LCAF / TT',
            'incoterm' => 'CFR',
            'mark' => 'N/M',
            'declaration' => 'We declare that this invoice shows the actual price of the goods described',
            'items' => [$this->itemPayload()],
        ], $overrides);
    }

    public function test_staff_can_create_proforma_invoice_with_shipping_and_bank_details(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create', 'proforma-invoices.view');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.proforma-invoices.store'),
            $this->invoicePayload($currency),
        );

        $proformaInvoice = ProformaInvoice::query()->where('invoice_no', 'AMEEN/NST/01/2023')->first();

        $this->assertNotNull($proformaInvoice);
        $response->assertRedirect(route('admin.proforma-invoices.show', $proformaInvoice));
        $this->assertSame('NEW SPREADING TRADE (HK) LIMITED', $proformaInvoice->buyer_name);
        $this->assertSame('AMEEN HK TRADING CO.,LIMITED', $proformaInvoice->exporter_name);
        $this->assertSame('HSBCCNSHGZH', $proformaInvoice->advising_bank_swift);
        $this->assertSame('009-381559-055(USD)', $proformaInvoice->beneficiary_account);
        $this->assertSame('CHATTOGRAM, BANGLADESH', $proformaInvoice->port_of_discharge);
        $this->assertSame('CFR', $proformaInvoice->incoterm);
        $this->assertSame('N/M', $proformaInvoice->mark);

        $item = $proformaInvoice->items->first();
        $this->assertSame('5407.61.00', $item->hs_code);
        $this->assertSame('YDS', $item->unit);
        $this->assertSame('1.85', $item->rate);
    }

    public function test_shipping_and_bank_details_are_optional(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create', 'proforma-invoices.view');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.proforma-invoices.store'), [
            'invoice_no' => 'PI-1001',
            'invoice_date' => '2026-11-13',
            'currency_id' => $currency->id,
            'buyer_name' => 'NEW SPREADING TRADE (HK) LIMITED',
            'items' => [$this->itemPayload()],
        ]);

        $proformaInvoice = ProformaInvoice::query()->where('invoice_no', 'PI-1001')->first();

        $this->assertNotNull($proformaInvoice);
        $response->assertRedirect(route('admin.proforma-invoices.show', $proformaInvoice));
        $this->assertFalse($proformaInvoice->hasAdvisingBankDetails());
        $this->assertFalse($proformaInvoice->hasShippingDetails());
    }

    public function test_buyer_name_is_required(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.proforma-invoices.store'),
            $this->invoicePayload($currency, ['buyer_name' => null]),
        );

        $response->assertSessionHasErrors('buyer_name');
        $this->assertSame(0, ProformaInvoice::count());
    }

    public function test_proforma_invoice_requires_at_least_one_item(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create');
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.proforma-invoices.store'),
            $this->invoicePayload($currency, ['items' => []]),
        );

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, ProformaInvoice::count());
    }

    public function test_invoice_number_must_be_unique(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create');
        $existing = ProformaInvoice::factory()->create();

        $response = $this->actingAs($staff)->post(route('admin.proforma-invoices.store'), [
            'invoice_no' => $existing->invoice_no,
            'invoice_date' => '2026-11-13',
            'currency_id' => $existing->currency_id,
            'buyer_name' => 'NEW SPREADING TRADE (HK) LIMITED',
            'items' => [$this->itemPayload()],
        ]);

        $response->assertSessionHasErrors('invoice_no');
    }

    public function test_total_amount_sums_the_line_items(): void
    {
        $proformaInvoice = ProformaInvoice::factory()->create();
        ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id, 'amount' => '925.00']);
        ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id, 'amount' => '75.00']);

        $proformaInvoice->load('items');

        $this->assertSame(1000.0, $proformaInvoice->totalAmount());
    }

    public function test_amount_in_words_spells_the_invoice_total(): void
    {
        $currency = Currency::factory()->create(['name' => 'US Dollars', 'code' => 'USD']);
        $proformaInvoice = ProformaInvoice::factory()->create(['currency_id' => $currency->id]);
        ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id, 'amount' => '925.50']);

        $proformaInvoice->load(['currency', 'items']);

        $this->assertSame('Nine Hundred Twenty Five and 50/100 US Dollars Only', $proformaInvoice->amountInWords());
    }

    public function test_quantity_label_joins_quantity_and_unit(): void
    {
        $item = ProformaInvoiceItem::factory()->make(['quantity' => '500.00', 'unit' => 'YDS']);
        $this->assertSame('500 YDS', $item->quantityLabel());

        $withoutUnit = ProformaInvoiceItem::factory()->make(['quantity' => '12.50', 'unit' => null]);
        $this->assertSame('12.5', $withoutUnit->quantityLabel());

        $withoutQuantity = ProformaInvoiceItem::factory()->make(['quantity' => null, 'unit' => 'PCS']);
        $this->assertSame('PCS', $withoutQuantity->quantityLabel());
    }

    public function test_updating_proforma_invoice_syncs_items_and_order(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.edit', 'proforma-invoices.view');
        $proformaInvoice = ProformaInvoice::factory()->create();

        $kept = ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id, 'sort_order' => 0]);
        $removed = ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id, 'sort_order' => 1]);

        $response = $this->actingAs($staff)->put(route('admin.proforma-invoices.update', $proformaInvoice), [
            'invoice_no' => $proformaInvoice->invoice_no,
            'invoice_date' => '2026-11-20',
            'currency_id' => $proformaInvoice->currency_id,
            'buyer_name' => 'Updated buyer',
            'port_of_discharge' => 'MONGLA, BANGLADESH',
            'items' => [
                $this->itemPayload(['description' => 'New first item']),
                $this->itemPayload(['id' => $kept->id, 'description' => 'Kept item moved last']),
            ],
        ]);

        $response->assertRedirect(route('admin.proforma-invoices.show', $proformaInvoice));

        $this->assertModelMissing($removed);
        $this->assertSame('Kept item moved last', $kept->refresh()->description);
        $this->assertSame('MONGLA, BANGLADESH', $proformaInvoice->refresh()->port_of_discharge);

        $this->assertSame(
            ['New first item', 'Kept item moved last'],
            $proformaInvoice->items->pluck('description')->all(),
        );
    }

    public function test_deleting_proforma_invoice_removes_its_items(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.delete');
        $item = ProformaInvoiceItem::factory()->create();
        $proformaInvoice = $item->proformaInvoice;

        $this->actingAs($staff)->delete(route('admin.proforma-invoices.destroy', $proformaInvoice));

        $this->assertModelMissing($proformaInvoice);
        $this->assertModelMissing($item);
    }

    public function test_show_page_renders_the_invoice(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.view');
        $proformaInvoice = ProformaInvoice::factory()->create(['incoterm' => 'CFR']);
        $item = ProformaInvoiceItem::factory()->create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'amount' => '925.00',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.proforma-invoices.show', $proformaInvoice))
            ->assertOk()
            ->assertSee($proformaInvoice->invoice_no)
            ->assertSee($proformaInvoice->buyer_name)
            ->assertSee($item->description)
            ->assertSee('CFR')
            ->assertSee(number_format(925.00, 2));
    }

    public function test_print_page_renders_the_proforma_invoice_document(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.view');
        $currency = Currency::factory()->create(['name' => 'US Dollars', 'code' => 'USD']);
        $proformaInvoice = ProformaInvoice::factory()->create([
            'currency_id' => $currency->id,
            'exporter_name' => 'AMEEN HK TRADING CO.,LIMITED',
            'buyer_name' => 'NEW SPREADING TRADE (HK) LIMITED',
            'advising_bank_swift' => 'HSBCCNSHGZH',
            'port_of_discharge' => 'CHATTOGRAM, BANGLADESH',
            'delivery_payment_terms' => 'BY LCAF / TT',
            'incoterm' => 'CFR',
            'mark' => 'N/M',
            'declaration' => 'We declare that this invoice shows the actual price of the goods described',
        ]);
        $item = ProformaInvoiceItem::factory()->create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'hs_code' => '5407.61.00',
            'amount' => '925.00',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.proforma-invoices.print', $proformaInvoice))
            ->assertOk()
            ->assertSee('Proforma Invoice')
            ->assertSee($proformaInvoice->invoice_no)
            ->assertSee('AMEEN HK TRADING CO.,LIMITED')
            ->assertSee('NEW SPREADING TRADE (HK) LIMITED')
            ->assertSee('HSBCCNSHGZH')
            ->assertSee('CHATTOGRAM, BANGLADESH')
            ->assertSee('BY LCAF / TT')
            ->assertSee($item->description)
            ->assertSee('5407.61.00')
            ->assertSee('N/M')
            ->assertSee('Authorised Signature')
            ->assertSee('We declare that this invoice shows the actual price of the goods described');
    }

    public function test_print_page_omits_empty_optional_blocks(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.view');
        $proformaInvoice = ProformaInvoice::factory()->minimal()->create();
        ProformaInvoiceItem::factory()->create(['proforma_invoice_id' => $proformaInvoice->id]);

        $this->actingAs($staff)
            ->get(route('admin.proforma-invoices.print', $proformaInvoice))
            ->assertOk()
            ->assertDontSee('ADVISING BANK')
            ->assertDontSee('Declaration');
    }

    public function test_pdf_download_is_generated(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.view');
        $proformaInvoice = ProformaInvoiceItem::factory()->create()->proformaInvoice;

        $response = $this->actingAs($staff)->get(route('admin.proforma-invoices.pdf', $proformaInvoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            "proforma-invoice-{$proformaInvoice->invoice_no}.pdf",
            $response->headers->get('content-disposition'),
        );
    }

    public function test_create_form_prefills_company_defaults(): void
    {
        $staff = $this->createStaffUser('proforma-invoices.create');
        Currency::factory()->create();

        Setting::set('company_name', 'BNoor Global Trading');
        Setting::set('bank_swift_code', 'HSBCCNSHGZH');
        Setting::set('proforma_invoice_declaration', 'We declare that all particulars are true and correct');

        $this->actingAs($staff)
            ->get(route('admin.proforma-invoices.create'))
            ->assertOk()
            ->assertSee('BNoor Global Trading')
            ->assertSee('HSBCCNSHGZH')
            ->assertSee('We declare that all particulars are true and correct');
    }

    public function test_staff_without_permission_cannot_create_proforma_invoice(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)
            ->get(route('admin.proforma-invoices.create'))
            ->assertForbidden();
    }

    public function test_staff_without_permission_cannot_view_proforma_invoices(): void
    {
        $staff = $this->createStaffUser();
        $proformaInvoice = ProformaInvoice::factory()->create();

        $this->actingAs($staff)->get(route('admin.proforma-invoices.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.proforma-invoices.print', $proformaInvoice))->assertForbidden();
    }
}
