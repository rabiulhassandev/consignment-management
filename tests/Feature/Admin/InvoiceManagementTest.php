<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    private function customer(array $overrides = []): User
    {
        return User::factory()->customer()->create($overrides);
    }

    public function test_staff_can_create_invoice_with_items(): void
    {
        $staff = $this->createStaffUser('invoices.create', 'invoices.view');
        $currency = Currency::factory()->create();
        $customer = $this->customer(['name' => 'MIL']);

        $response = $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'customer_id' => $customer->id,
            'bill_to_address' => 'House 12, Gulshan, Dhaka, Bangladesh',
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [
                $this->itemPayload(),
                $this->itemPayload(['description' => 'Delivery fee', 'quantity' => null, 'rate' => null, 'amount' => '800']),
            ],
        ]);

        $invoice = Invoice::query()->latest('id')->first();

        $this->assertNotNull($invoice);
        $response->assertRedirect(route('admin.invoices.show', $invoice));
        $this->assertSame('INV-0001', $invoice->invoice_no);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame('MIL', $invoice->billToName());
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
            'customer_id' => $this->customer()->id,
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
            'customer_id' => $this->customer()->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [$this->itemPayload(['amount' => null])],
        ]);

        $response->assertSessionHasErrors('items.0.amount');
        $this->assertSame(0, Invoice::count());
    }

    public function test_invoice_requires_an_existing_customer(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $currency = Currency::factory()->create();

        $payload = [
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'items' => [$this->itemPayload()],
        ];

        $this->actingAs($staff)
            ->post(route('admin.invoices.store'), $payload)
            ->assertSessionHasErrors('customer_id');

        // A staff account is not a customer and must not be billable.
        $this->actingAs($staff)
            ->post(route('admin.invoices.store'), [...$payload, 'customer_id' => $staff->id])
            ->assertSessionHasErrors('customer_id');

        $this->assertSame(0, Invoice::count());
    }

    public function test_invoice_number_is_generated_and_a_submitted_number_is_ignored(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $existing = Invoice::factory()->create(['invoice_no' => 'INV-0007']);

        $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'invoice_no' => 'INV-0007',
            'customer_id' => $this->customer()->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $existing->currency_id,
            'items' => [$this->itemPayload()],
        ])->assertSessionHasNoErrors();

        $created = Invoice::query()->whereKeyNot($existing->id)->sole();

        $this->assertNotSame('INV-0007', $created->invoice_no);
        $this->assertStringStartsWith('INV-', $created->invoice_no);
    }

    public function test_invoice_number_cannot_be_changed_on_update(): void
    {
        $staff = $this->createStaffUser('invoices.edit', 'invoices.view');
        $invoice = Invoice::factory()->create(['invoice_no' => 'INV-0009']);

        $this->actingAs($staff)->put(route('admin.invoices.update', $invoice), [
            'invoice_no' => 'INV-9999',
            'customer_id' => $invoice->customer_id,
            'invoice_date' => '2026-07-05',
            'currency_id' => $invoice->currency_id,
            'items' => [$this->itemPayload()],
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame('INV-0009', $invoice->refresh()->invoice_no);
    }

    public function test_updating_invoice_syncs_items_and_order(): void
    {
        $staff = $this->createStaffUser('invoices.edit', 'invoices.view');
        $invoice = Invoice::factory()->create();
        $newCustomer = $this->customer(['name' => 'Updated client']);

        $kept = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 0]);
        $removed = InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'sort_order' => 1]);

        $response = $this->actingAs($staff)->put(route('admin.invoices.update', $invoice), [
            'customer_id' => $newCustomer->id,
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
        $this->assertSame($newCustomer->id, $invoice->refresh()->customer_id);

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

    public function test_invoice_form_locks_the_invoice_number_and_lists_customers(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $customer = $this->customer(['name' => 'Fabric House Ltd']);

        $this->actingAs($staff)
            ->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertSee('id="invoice_no" readonly', false)
            ->assertDontSee('name="invoice_no"', false)
            ->assertSee('name="customer_id"', false)
            ->assertDontSee('name="bill_to"', false)
            ->assertSee('Fabric House Ltd');
    }

    public function test_invoice_edit_form_keeps_the_number_readonly_and_preselects_the_customer(): void
    {
        $staff = $this->createStaffUser('invoices.edit');
        $invoice = Invoice::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('id="invoice_no" readonly', false)
            ->assertSee($invoice->invoice_no)
            ->assertDontSee('name="invoice_no"', false)
            ->assertSee('value="'.$invoice->customer_id.'" selected', false);
    }

    public function test_invoices_can_be_searched_by_customer_name(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $match = Invoice::factory()->create(['customer_id' => $this->customer(['name' => 'Searchable Trading'])->id]);
        $other = Invoice::factory()->create(['customer_id' => $this->customer(['name' => 'Unrelated Imports'])->id]);

        $this->actingAs($staff)
            ->get(route('admin.invoices.index', ['search' => 'Searchable']))
            ->assertOk()
            ->assertSee($match->invoice_no)
            ->assertDontSee($other->invoice_no);
    }

    public function test_legacy_invoice_without_a_customer_still_renders_its_bill_to_name(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = Invoice::factory()->legacy()->create(['bill_to' => 'Old Client Ltd']);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertNull($invoice->customer_id);
        $this->assertSame('Old Client Ltd', $invoice->billToName());

        $this->actingAs($staff)->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('Old Client Ltd');
        $this->actingAs($staff)->get(route('admin.invoices.print', $invoice))->assertOk()->assertSee('Old Client Ltd');
        $this->actingAs($staff)->get(route('admin.invoices.index', ['search' => 'Old Client']))->assertOk()->assertSee($invoice->invoice_no);
    }

    public function test_billing_address_falls_back_to_the_customer_address(): void
    {
        $customer = $this->customer(['address' => 'Plot 7, Nanhai, Foshan, China']);

        $withOverride = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'bill_to_address' => 'Warehouse 3, Chittagong',
        ]);
        $withoutOverride = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'bill_to_address' => null,
        ]);

        $this->assertSame('Warehouse 3, Chittagong', $withOverride->billToAddress());
        $this->assertSame('Plot 7, Nanhai, Foshan, China', $withoutOverride->billToAddress());
    }

    /**
     * Raise an invoice for a customer on a given date, with one line item and an
     * optional collection, so a wallet history can be built up in order.
     */
    private function walletInvoice(
        User $customer,
        Currency $currency,
        string $date,
        float $amount,
        float $received = 0,
    ): Invoice {
        $invoice = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'invoice_date' => $date,
            'received_amount' => $received,
        ]);

        InvoiceItem::factory()->amountOnly()->create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);

        return $invoice->refresh();
    }

    public function test_wallet_balance_runs_forward_across_a_customers_invoices(): void
    {
        $customer = $this->customer();
        $bdt = Currency::factory()->create(['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳']);

        // Invoice 1: nothing collected against a 10,000 bill.
        $first = $this->walletInvoice($customer, $bdt, '2026-07-01', 10000, 0);
        $this->assertSame(0.0, $first->openingWalletBalance());
        $this->assertSame(-10000.0, $first->closingWalletBalance());

        // Invoice 2: opens 10,000 in debt, collects 15,000 against a 1,000 bill.
        $second = $this->walletInvoice($customer, $bdt, '2026-07-02', 1000, 15000);
        $this->assertSame(-10000.0, $second->openingWalletBalance());
        $this->assertSame(4000.0, $second->closingWalletBalance());

        // Invoice 3: opens on the advance left over, collects nothing against a 2,000 bill.
        $third = $this->walletInvoice($customer, $bdt, '2026-07-03', 2000, 0);
        $this->assertSame(4000.0, $third->openingWalletBalance());
        $this->assertSame(2000.0, $third->closingWalletBalance());

        $this->assertSame(
            [$bdt->id => 2000.0],
            Invoice::walletBalancesByCustomer()[$customer->id],
        );
    }

    public function test_wallet_balances_are_tracked_separately_per_currency(): void
    {
        $customer = $this->customer();
        $bdt = Currency::factory()->create(['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳']);
        $cny = Currency::factory()->create(['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥']);

        $this->walletInvoice($customer, $bdt, '2026-07-01', 10000, 13000);

        // A CNY invoice opens at zero: the BDT advance is never converted into it.
        $cnyInvoice = $this->walletInvoice($customer, $cny, '2026-07-02', 2000, 12000);

        $this->assertSame(0.0, $cnyInvoice->openingWalletBalance());
        $this->assertSame(10000.0, $cnyInvoice->closingWalletBalance());

        $this->assertSame(
            [$bdt->id => 3000.0, $cny->id => 10000.0],
            Invoice::walletBalancesByCustomer()[$customer->id],
        );
    }

    public function test_wallet_balances_are_scoped_to_one_customer(): void
    {
        $currency = Currency::factory()->create();
        $customer = $this->customer();
        $other = $this->customer();

        $this->walletInvoice($customer, $currency, '2026-07-01', 5000, 0);
        $invoice = $this->walletInvoice($other, $currency, '2026-07-02', 1000, 4000);

        $this->assertSame(0.0, $invoice->openingWalletBalance());
        $this->assertSame(3000.0, $invoice->closingWalletBalance());
    }

    public function test_wallet_balance_follows_invoice_date_then_id_order(): void
    {
        $customer = $this->customer();
        $currency = Currency::factory()->create();

        // Created second but dated first, so it must come first in the running balance.
        $later = $this->walletInvoice($customer, $currency, '2026-07-10', 1000, 0);
        $earlier = $this->walletInvoice($customer, $currency, '2026-07-01', 4000, 0);

        $this->assertSame(0.0, $earlier->openingWalletBalance());
        $this->assertSame(-4000.0, $later->openingWalletBalance());
        $this->assertSame(-5000.0, $later->closingWalletBalance());
    }

    public function test_legacy_invoice_without_a_customer_has_no_wallet_activity(): void
    {
        $invoice = Invoice::factory()->legacy()->create();
        InvoiceItem::factory()->amountOnly()->create(['invoice_id' => $invoice->id, 'amount' => 500]);

        $this->assertSame(0.0, $invoice->refresh()->openingWalletBalance());
        $this->assertFalse($invoice->hasWalletActivity());
    }

    public function test_staff_can_record_a_collection_when_creating_an_invoice(): void
    {
        $staff = $this->createStaffUser('invoices.create', 'invoices.view');
        $currency = Currency::factory()->create();
        $customer = $this->customer();

        $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'received_amount' => '15000.50',
            'items' => [$this->itemPayload(['amount' => '10000'])],
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::query()->sole();

        $this->assertSame('15000.50', $invoice->received_amount);
        $this->assertSame(15000.50, $invoice->receivedAmount());
        $this->assertSame(5000.50, $invoice->closingWalletBalance());
    }

    public function test_a_blank_collection_box_is_stored_as_zero(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $currency = Currency::factory()->create();

        $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'customer_id' => $this->customer()->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'received_amount' => '',
            'items' => [$this->itemPayload()],
        ])->assertSessionHasNoErrors();

        $this->assertSame('0.00', Invoice::query()->sole()->received_amount);
    }

    public function test_collection_amount_must_not_be_negative(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $currency = Currency::factory()->create();

        $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'customer_id' => $this->customer()->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'received_amount' => '-500',
            'items' => [$this->itemPayload()],
        ])->assertSessionHasErrors('received_amount');

        $this->assertSame(0, Invoice::count());
    }

    public function test_staff_can_correct_a_collection_when_editing_an_invoice(): void
    {
        $staff = $this->createStaffUser('invoices.edit', 'invoices.view');
        $customer = $this->customer();
        $currency = Currency::factory()->create();
        $invoice = $this->walletInvoice($customer, $currency, '2026-07-01', 1000, 500);

        $this->actingAs($staff)->put(route('admin.invoices.update', $invoice), [
            'customer_id' => $customer->id,
            'invoice_date' => '2026-07-01',
            'currency_id' => $currency->id,
            'received_amount' => '900',
            'items' => [$this->itemPayload(['amount' => '1000'])],
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $this->assertSame('900.00', $invoice->refresh()->received_amount);
        $this->assertSame(-100.0, $invoice->refresh()->closingWalletBalance());
    }

    public function test_create_form_carries_the_wallet_balances_for_every_customer(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $customer = $this->customer();
        $currency = Currency::factory()->create();

        $this->walletInvoice($customer, $currency, '2026-07-01', 10000, 0);

        $this->actingAs($staff)
            ->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertSee('Customer Wallet')
            ->assertSee('name="received_amount"', false)
            ->assertViewHas('walletBalances', [$customer->id => [$currency->id => -10000.0]]);
    }

    public function test_edit_form_shows_the_balance_as_it_stood_before_this_invoice(): void
    {
        $staff = $this->createStaffUser('invoices.edit');
        $customer = $this->customer();
        $currency = Currency::factory()->create();

        $first = $this->walletInvoice($customer, $currency, '2026-07-01', 10000, 0);
        $second = $this->walletInvoice($customer, $currency, '2026-07-02', 1000, 15000);

        // Editing the second invoice must exclude its own figures from the balance shown.
        $this->actingAs($staff)
            ->get(route('admin.invoices.edit', $second))
            ->assertOk()
            ->assertViewHas('walletBalances', [$customer->id => [$currency->id => -10000.0]]);

        // Editing the first invoice excludes only itself, leaving the second's figures.
        $this->actingAs($staff)
            ->get(route('admin.invoices.edit', $first))
            ->assertOk()
            ->assertViewHas('walletBalances', [$customer->id => [$currency->id => 14000.0]]);
    }

    public function test_invoice_show_page_renders_the_wallet_card(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $customer = $this->customer();
        $currency = Currency::factory()->create(['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳']);

        $this->walletInvoice($customer, $currency, '2026-07-01', 10000, 0);
        $second = $this->walletInvoice($customer, $currency, '2026-07-02', 1000, 15000);

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $second))
            ->assertOk()
            ->assertSee('Customer Wallet')
            ->assertSee('Opening Balance')
            ->assertSee('-10,000.00')
            ->assertSee('15,000.00')
            ->assertSee('4,000.00');
    }

    public function test_invoice_documents_show_the_account_summary_only_when_the_wallet_is_in_play(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $customer = $this->customer();
        $currency = Currency::factory()->create(['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳']);

        $quiet = $this->walletInvoice($customer, $currency, '2026-07-01', 10000, 0);

        // Nothing collected and no balance carried in: the document is unchanged.
        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $quiet))
            ->assertOk()
            ->assertDontSee('Previous Balance')
            ->assertDontSee('Balance Due');

        $active = $this->walletInvoice($customer, $currency, '2026-07-02', 1000, 15000);

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $active))
            ->assertOk()
            ->assertSee('Previous Balance')
            ->assertSee('-10,000.00')
            ->assertSee('Balance In Hand (BDT)')
            ->assertSee('4,000.00');
    }

    public function test_invoice_pdf_document_renders_the_account_summary(): void
    {
        $customer = $this->customer();
        $currency = Currency::factory()->create(['code' => 'BDT', 'name' => 'Bangladeshi Taka', 'symbol' => '৳']);

        $this->walletInvoice($customer, $currency, '2026-07-01', 10000, 0);
        $invoice = $this->walletInvoice($customer, $currency, '2026-07-02', 5000, 1000);
        $invoice->load(['currency', 'customer', 'items']);

        $html = view('admin.invoices.pdf', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->totalAmount(),
            'amountInWords' => $invoice->amountInWords(),
            'openingBalance' => $invoice->openingWalletBalance(),
            'closingBalance' => $invoice->closingWalletBalance(),
        ])->render();

        $this->assertStringContainsString('Previous Balance', $html);
        $this->assertStringContainsString('Balance Due (BDT)', $html);
        $this->assertStringContainsString('14,000.00', $html);
    }

    /**
     * The six payment detail settings used as the site-wide defaults.
     */
    private function setDefaultPaymentSettings(): void
    {
        Setting::set('bank_name', 'The City Bank Limited');
        Setting::set('bank_account_name', 'Amatullah Trade International');
        Setting::set('bank_account_number', '1504311841001');
        Setting::set('bank_branch', 'Sonargaon Janapath Branch, Uttara, Dhaka 1230');
        Setting::set('bank_swift_code', 'CIBLBDDH');
        Setting::set('bank_routing_number', '225264535');
    }

    public function test_create_form_prefills_payment_details_from_the_site_settings(): void
    {
        $staff = $this->createStaffUser('invoices.create');
        $this->setDefaultPaymentSettings();

        $this->actingAs($staff)
            ->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertSee('value="The City Bank Limited"', false)
            ->assertSee('value="Amatullah Trade International"', false)
            ->assertSee('value="1504311841001"', false)
            ->assertSee('value="Sonargaon Janapath Branch, Uttara, Dhaka 1230"', false)
            ->assertSee('value="CIBLBDDH"', false)
            ->assertSee('value="225264535"', false);
    }

    public function test_staff_can_save_payment_details_on_an_invoice(): void
    {
        $staff = $this->createStaffUser('invoices.create', 'invoices.view');
        $currency = Currency::factory()->create();
        $this->setDefaultPaymentSettings();

        $this->actingAs($staff)->post(route('admin.invoices.store'), [
            'customer_id' => $this->customer()->id,
            'invoice_date' => '2026-07-02',
            'currency_id' => $currency->id,
            'bank_name' => 'Dutch-Bangla Bank PLC',
            'bank_account_name' => 'BNoor Global Trading',
            'bank_account_number' => '7017100005678',
            'bank_branch' => 'Banani Branch, Dhaka',
            'bank_swift_code' => 'DBBLBDDH',
            'bank_routing_number' => '090261726',
            'items' => [$this->itemPayload()],
        ])->assertSessionHasNoErrors();

        $invoice = Invoice::query()->sole();

        $this->assertSame('Dutch-Bangla Bank PLC', $invoice->bank_name);
        $this->assertSame('7017100005678', $invoice->bank_account_number);
        $this->assertSame([
            'Bank' => 'Dutch-Bangla Bank PLC',
            'Account Name' => 'BNoor Global Trading',
            'Account No.' => '7017100005678',
            'Branch' => 'Banani Branch, Dhaka',
            'SWIFT / BIC' => 'DBBLBDDH',
            'Routing No.' => '090261726',
        ], $invoice->printedPaymentDetails());

        // The invoice's own account is printed, not the site default.
        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Dutch-Bangla Bank PLC')
            ->assertSee('7017100005678')
            ->assertDontSee('The City Bank Limited')
            ->assertDontSee('1504311841001');
    }

    public function test_staff_can_update_payment_details_on_an_invoice(): void
    {
        $staff = $this->createStaffUser('invoices.edit', 'invoices.view');
        $invoice = Invoice::factory()->create(['bank_name' => 'The City Bank Limited']);

        $this->actingAs($staff)->put(route('admin.invoices.update', $invoice), [
            'customer_id' => $invoice->customer_id,
            'invoice_date' => '2026-07-05',
            'currency_id' => $invoice->currency_id,
            'bank_name' => 'Standard Chartered Bangladesh',
            'bank_account_number' => '0123456789',
            'items' => [$this->itemPayload()],
        ])->assertRedirect(route('admin.invoices.show', $invoice));

        $invoice->refresh();

        $this->assertSame('Standard Chartered Bangladesh', $invoice->bank_name);
        $this->assertSame('0123456789', $invoice->bank_account_number);
    }

    public function test_invoice_without_payment_details_falls_back_to_the_site_defaults(): void
    {
        $this->setDefaultPaymentSettings();
        $invoice = Invoice::factory()->create();

        $this->assertNull($invoice->bank_name);
        $this->assertSame([
            'Bank' => 'The City Bank Limited',
            'Account Name' => 'Amatullah Trade International',
            'Account No.' => '1504311841001',
            'Branch' => 'Sonargaon Janapath Branch, Uttara, Dhaka 1230',
            'SWIFT / BIC' => 'CIBLBDDH',
            'Routing No.' => '225264535',
        ], $invoice->printedPaymentDetails());
    }

    public function test_a_cleared_payment_detail_does_not_fall_back_to_the_site_default(): void
    {
        $this->setDefaultPaymentSettings();

        // This invoice carries its own account, with the routing number deliberately left out.
        $invoice = Invoice::factory()->create([
            'bank_name' => 'Dutch-Bangla Bank PLC',
            'bank_account_number' => '7017100005678',
            'bank_routing_number' => null,
        ]);

        $printed = $invoice->printedPaymentDetails();

        $this->assertSame('Dutch-Bangla Bank PLC', $printed['Bank']);
        $this->assertArrayNotHasKey('Routing No.', $printed);
        $this->assertArrayNotHasKey('Account Name', $printed);
    }

    public function test_edit_form_shows_the_payment_details_that_the_invoice_prints(): void
    {
        $staff = $this->createStaffUser('invoices.edit');
        $this->setDefaultPaymentSettings();

        $withOwn = Invoice::factory()->create(['bank_name' => 'Dutch-Bangla Bank PLC']);
        $withDefaults = Invoice::factory()->create();

        $this->actingAs($staff)
            ->get(route('admin.invoices.edit', $withOwn))
            ->assertOk()
            ->assertSee('value="Dutch-Bangla Bank PLC"', false)
            ->assertDontSee('value="The City Bank Limited"', false);

        $this->actingAs($staff)
            ->get(route('admin.invoices.edit', $withDefaults))
            ->assertOk()
            ->assertSee('value="The City Bank Limited"', false);
    }

    public function test_invoice_show_page_renders_the_payment_details_card(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $this->setDefaultPaymentSettings();

        $withOwn = InvoiceItem::factory()->create()->invoice;
        $withOwn->update([
            'bank_name' => 'Dutch-Bangla Bank PLC',
            'bank_account_number' => '7017100005678',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $withOwn))
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertSee('This invoice')
            ->assertSee('Dutch-Bangla Bank PLC')
            ->assertSee('7017100005678')
            ->assertDontSee('The City Bank Limited')
            ->assertDontSee('Routing No.');

        $withDefaults = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $withDefaults))
            ->assertOk()
            ->assertSee('Payment Details')
            ->assertSee('Site default')
            ->assertSee('The City Bank Limited')
            ->assertSee('Sonargaon Janapath Branch, Uttara, Dhaka 1230')
            ->assertSee('225264535');
    }

    public function test_invoice_show_page_omits_the_payment_details_card_when_none_are_configured(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff)
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Payment Details');
    }

    public function test_invoice_excel_uses_the_invoice_payment_details(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $this->setDefaultPaymentSettings();
        $invoice = InvoiceItem::factory()->create()->invoice;
        $invoice->update(['bank_name' => 'Dutch-Bangla Bank PLC', 'bank_account_number' => '7017100005678']);

        $this->actingAs($staff);
        $values = $this->excelCellValues($invoice);

        $this->assertContains('PAYMENT DETAILS', $values);
        $this->assertContains('Dutch-Bangla Bank PLC', $values);
        $this->assertContains('7017100005678', $values);
        $this->assertNotContains('The City Bank Limited', $values);
    }

    public function test_invoice_pdf_document_renders_the_invoice_payment_details(): void
    {
        $this->setDefaultPaymentSettings();
        $invoice = InvoiceItem::factory()->create()->invoice;
        $invoice->update(['bank_name' => 'Dutch-Bangla Bank PLC']);
        $invoice->load(['currency', 'customer', 'items']);

        $html = view('admin.invoices.pdf', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->totalAmount(),
            'amountInWords' => $invoice->amountInWords(),
        ])->render();

        $this->assertStringContainsString('Payment Details', $html);
        $this->assertStringContainsString('Dutch-Bangla Bank PLC', $html);
        $this->assertStringNotContainsString('The City Bank Limited', $html);
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
            ->assertSee($invoice->customer->name)
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

    public function test_amount_in_words_spells_the_invoice_total(): void
    {
        $currency = Currency::factory()->create(['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥']);
        $invoice = Invoice::factory()->create(['currency_id' => $currency->id]);
        InvoiceItem::factory()->amountOnly()->create(['invoice_id' => $invoice->id, 'amount' => '10000.00']);

        $this->assertSame('Ten Thousand (CNY) Only', $invoice->refresh()->amountInWords());

        InvoiceItem::factory()->amountOnly()->create(['invoice_id' => $invoice->id, 'amount' => '25.50']);

        $this->assertSame('Ten Thousand Twenty Five and 50/100 (CNY) Only', $invoice->refresh()->amountInWords());
    }

    public function test_invoice_print_page_shows_the_total_in_words(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $currency = Currency::factory()->create(['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥']);
        $invoice = Invoice::factory()->create(['currency_id' => $currency->id]);
        InvoiceItem::factory()->amountOnly()->create(['invoice_id' => $invoice->id, 'amount' => '10000.00']);

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('In Words')
            ->assertSee('Ten Thousand (CNY) Only');
    }

    public function test_invoice_pdf_document_shows_the_total_in_words(): void
    {
        $currency = Currency::factory()->create(['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥']);
        $invoice = Invoice::factory()->create(['currency_id' => $currency->id]);
        InvoiceItem::factory()->amountOnly()->create(['invoice_id' => $invoice->id, 'amount' => '10000.00']);
        $invoice->load(['currency', 'customer', 'items']);

        $html = view('admin.invoices.pdf', [
            'invoice' => $invoice,
            'totalAmount' => $invoice->totalAmount(),
            'amountInWords' => $invoice->amountInWords(),
        ])->render();

        $this->assertStringContainsString('In Words', $html);
        $this->assertStringContainsString('Ten Thousand (CNY) Only', $html);
    }

    public function test_invoice_print_page_renders_the_company_seal_watermark(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        Setting::set('site_seal', 'seals/company-seal.png');

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertSee('seals/company-seal.png', false);
    }

    public function test_invoice_print_page_omits_the_watermark_when_no_seal_is_configured(): void
    {
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        $this->actingAs($staff)
            ->get(route('admin.invoices.print', $invoice))
            ->assertOk()
            ->assertDontSee('seals/', false);
    }

    public function test_invoice_pdf_document_embeds_the_company_seal_watermark(): void
    {
        Storage::fake('public');
        $invoice = InvoiceItem::factory()->create()->invoice;
        $invoice->load(['currency', 'customer', 'items']);

        Setting::set('site_seal', UploadedFile::fake()->image('seal.png')->store('seals', 'public'));

        $data = [
            'invoice' => $invoice,
            'totalAmount' => $invoice->totalAmount(),
            'amountInWords' => $invoice->amountInWords(),
        ];

        $this->assertStringContainsString('class="seal"', view('admin.invoices.pdf', $data)->render());

        Setting::set('site_seal', null);

        $this->assertStringNotContainsString('class="seal"', view('admin.invoices.pdf', $data)->render());
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

    public function test_invoice_pdf_download_is_generated_with_a_seal_configured(): void
    {
        Storage::fake('public');
        $staff = $this->createStaffUser('invoices.view');
        $invoice = InvoiceItem::factory()->create()->invoice;

        Setting::set('site_seal', UploadedFile::fake()->image('seal.png', 400, 400)->store('seals', 'public'));

        $this->actingAs($staff)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
        $invoice = Invoice::factory()->create();
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
            'customer_id' => $this->customer(['name' => 'MIL'])->id,
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
        $this->assertContains('One Hundred Twenty Eight Thousand One Hundred (CNY) Only', $values);
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
