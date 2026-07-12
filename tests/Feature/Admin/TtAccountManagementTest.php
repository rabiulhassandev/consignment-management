<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\TtAccount;
use App\Models\TtAccountEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TtAccountManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function accountPayload(User $customer, Currency $currency, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'title' => 'SPF CHINA TT ACCOUNTS 2026',
            'currency_id' => $currency->id,
            'opening_balance' => null,
            'status' => 'open',
        ], $overrides);
    }

    private function entryPayload(array $overrides = []): array
    {
        return array_merge([
            'entry_date' => null,
            'description' => 'FAZLE RABBI VY PAID CASH',
            'type' => 'paid',
            'amount' => '2000',
            'source_currency_id' => null,
            'source_amount' => null,
            'source_rate' => null,
            'remarks' => null,
        ], $overrides);
    }

    public function test_staff_can_create_tt_account(): void
    {
        $staff = $this->createStaffUser('tt-accounts.create', 'tt-accounts.view');
        $customer = User::factory()->customer()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.tt-accounts.store'),
            $this->accountPayload($customer, $currency, ['opening_balance' => '100']),
        );

        $ttAccount = TtAccount::query()->where('title', 'SPF CHINA TT ACCOUNTS 2026')->first();

        $this->assertNotNull($ttAccount);
        $response->assertRedirect(route('admin.tt-accounts.show', $ttAccount));
        $this->assertSame($customer->id, $ttAccount->customer_id);
        $this->assertSame('100.00', $ttAccount->opening_balance);
    }

    public function test_customer_must_be_a_registered_customer(): void
    {
        $staff = $this->createStaffUser('tt-accounts.create');
        $otherStaff = User::factory()->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.tt-accounts.store'),
            $this->accountPayload($otherStaff, $currency),
        );

        $response->assertSessionHasErrors('customer_id');
        $this->assertSame(0, TtAccount::count());
    }

    public function test_staff_can_add_entry_to_account(): void
    {
        $staff = $this->createStaffUser('tt-accounts.edit', 'tt-accounts.view');
        $ttAccount = TtAccount::factory()->create();

        $response = $this->actingAs($staff)->post(
            route('admin.tt-accounts.entries.store', $ttAccount),
            $this->entryPayload(),
        );

        $response->assertRedirect(route('admin.tt-accounts.show', $ttAccount));
        $this->assertCount(1, $ttAccount->entries);
        $this->assertSame('FAZLE RABBI VY PAID CASH', $ttAccount->entries->sole()->description);
    }

    public function test_source_conversion_entry_stores_currency_amount_and_rate(): void
    {
        $staff = $this->createStaffUser('tt-accounts.edit', 'tt-accounts.view');
        $ttAccount = TtAccount::factory()->create();
        $bdt = Currency::factory()->create(['code' => 'BDT', 'symbol' => '৳']);

        $this->actingAs($staff)->post(route('admin.tt-accounts.entries.store', $ttAccount), $this->entryPayload([
            'description' => 'RECEIVED BDT 134810 TK',
            'type' => 'received',
            'source_currency_id' => $bdt->id,
            'source_amount' => '134810',
            'source_rate' => '18',
            'amount' => '7489.44',
        ]));

        $entry = $ttAccount->entries->sole();

        $this->assertSame($bdt->id, $entry->source_currency_id);
        $this->assertSame('134810.00', $entry->source_amount);
        $this->assertSame('18.0000', $entry->source_rate);
        $this->assertSame('7489.44', $entry->amount);
    }

    public function test_entry_update_and_delete_are_scoped_to_the_account(): void
    {
        $staff = $this->createStaffUser('tt-accounts.edit');
        $ttAccount = TtAccount::factory()->create();
        $foreignEntry = TtAccountEntry::factory()->create();

        $this->actingAs($staff)
            ->put(route('admin.tt-accounts.entries.update', [$ttAccount, $foreignEntry]), $this->entryPayload())
            ->assertNotFound();

        $this->actingAs($staff)
            ->delete(route('admin.tt-accounts.entries.destroy', [$ttAccount, $foreignEntry]))
            ->assertNotFound();

        $this->assertModelExists($foreignEntry);
    }

    public function test_show_page_computes_running_balance_in_insertion_order(): void
    {
        $staff = $this->createStaffUser('tt-accounts.view');
        $ttAccount = TtAccount::factory()->create(['opening_balance' => '100']);

        TtAccountEntry::factory()->received()->create([
            'tt_account_id' => $ttAccount->id,
            'entry_date' => null,
            'amount' => '50',
        ]);
        TtAccountEntry::factory()->paid()->create([
            'tt_account_id' => $ttAccount->id,
            'entry_date' => '2026-01-01',
            'amount' => '30',
        ]);

        $this->actingAs($staff)
            ->get(route('admin.tt-accounts.show', $ttAccount))
            ->assertOk()
            ->assertSeeInOrder(['150.00', '120.00']);
    }

    public function test_deleting_account_removes_its_entries(): void
    {
        $staff = $this->createStaffUser('tt-accounts.delete');
        $entry = TtAccountEntry::factory()->create();
        $ttAccount = $entry->ttAccount;

        $this->actingAs($staff)->delete(route('admin.tt-accounts.destroy', $ttAccount));

        $this->assertModelMissing($ttAccount);
        $this->assertModelMissing($entry);
    }

    public function test_entry_mutations_require_edit_permission(): void
    {
        $staff = $this->createStaffUser('tt-accounts.view');
        $ttAccount = TtAccount::factory()->create();

        $this->actingAs($staff)
            ->post(route('admin.tt-accounts.entries.store', $ttAccount), $this->entryPayload())
            ->assertForbidden();

        $this->assertSame(0, TtAccountEntry::count());
    }

    public function test_print_page_renders_statement(): void
    {
        $staff = $this->createStaffUser('tt-accounts.view');
        $entry = TtAccountEntry::factory()->converted()->create();
        $ttAccount = $entry->ttAccount;

        $this->actingAs($staff)
            ->get(route('admin.tt-accounts.print', $ttAccount))
            ->assertOk()
            ->assertSee($ttAccount->title)
            ->assertSee($entry->description)
            ->assertSee('7,489.44');
    }

    public function test_staff_without_permission_cannot_view_tt_accounts(): void
    {
        $staff = $this->createStaffUser();
        $ttAccount = TtAccount::factory()->create();

        $this->actingAs($staff)->get(route('admin.tt-accounts.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.tt-accounts.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.tt-accounts.print', $ttAccount))->assertForbidden();
    }
}
