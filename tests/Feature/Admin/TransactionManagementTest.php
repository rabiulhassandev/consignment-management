<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Build a valid store/update payload for the given category.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function transactionPayload(TransactionCategory $category, array $overrides = []): array
    {
        return array_merge([
            'type' => $category->type->value,
            'transaction_category_id' => $category->id,
            'transaction_date' => '2026-07-10',
            'amount' => '1500.50',
            'description' => 'Test entry',
        ], $overrides);
    }

    public function test_staff_with_permission_can_create_income_and_expense_entries(): void
    {
        $staff = $this->createStaffUser('transactions.view', 'transactions.create');
        $incomeCategory = TransactionCategory::factory()->income()->create();
        $expenseCategory = TransactionCategory::factory()->expense()->create();

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), $this->transactionPayload($incomeCategory, ['description' => 'Machine sale']))
            ->assertRedirect(route('admin.transactions.index'));

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), $this->transactionPayload($expenseCategory, ['amount' => '320.75', 'description' => 'Office rent']))
            ->assertRedirect(route('admin.transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'type' => 'income',
            'transaction_category_id' => $incomeCategory->id,
            'description' => 'Machine sale',
        ]);
        $this->assertDatabaseHas('transactions', [
            'type' => 'expense',
            'transaction_category_id' => $expenseCategory->id,
            'description' => 'Office rent',
        ]);
        $this->assertSame('1500.50', Transaction::income()->first()->amount);
        $this->assertSame('320.75', Transaction::expense()->first()->amount);
    }

    public function test_entry_rejects_category_of_the_other_type(): void
    {
        $staff = $this->createStaffUser('transactions.create');
        $incomeCategory = TransactionCategory::factory()->income()->create();
        $expenseCategory = TransactionCategory::factory()->expense()->create();

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), $this->transactionPayload($expenseCategory, ['type' => 'income']))
            ->assertSessionHasErrors('transaction_category_id');

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), $this->transactionPayload($incomeCategory, ['type' => 'expense']))
            ->assertSessionHasErrors('transaction_category_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_entry_requires_valid_fields(): void
    {
        $staff = $this->createStaffUser('transactions.create');
        $category = TransactionCategory::factory()->expense()->create();

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), [])
            ->assertSessionHasErrors(['type', 'transaction_category_id', 'transaction_date', 'amount']);

        $this->actingAs($staff)
            ->post(route('admin.transactions.store'), $this->transactionPayload($category, ['amount' => '0']))
            ->assertSessionHasErrors('amount');
    }

    public function test_staff_with_permission_can_update_and_delete_entries(): void
    {
        $staff = $this->createStaffUser('transactions.edit', 'transactions.delete');
        $transaction = Transaction::factory()->income()->create();
        $newCategory = TransactionCategory::factory()->income()->create();

        $this->actingAs($staff)
            ->put(route('admin.transactions.update', $transaction), $this->transactionPayload($newCategory, [
                'amount' => '250.75',
                'description' => 'Updated entry',
            ]))
            ->assertRedirect(route('admin.transactions.index'));

        $transaction->refresh();
        $this->assertSame('250.75', $transaction->amount);
        $this->assertSame($newCategory->id, $transaction->transaction_category_id);
        $this->assertSame('Updated entry', $transaction->description);

        $this->actingAs($staff)->delete(route('admin.transactions.destroy', $transaction));
        $this->assertModelMissing($transaction);
    }

    public function test_index_filters_by_type_category_and_date_range(): void
    {
        $staff = $this->createStaffUser('transactions.view');
        Transaction::factory()->income()->onDate('2026-01-05')->create(['description' => 'January salary income']);
        Transaction::factory()->expense()->onDate('2026-02-10')->create(['description' => 'February office rent']);
        $fuel = Transaction::factory()->expense()->onDate('2026-02-15')->create(['description' => 'February fuel cost']);

        $this->actingAs($staff)
            ->get(route('admin.transactions.index', ['type' => 'income']))
            ->assertOk()
            ->assertSee('January salary income')
            ->assertDontSee('February office rent');

        $this->actingAs($staff)
            ->get(route('admin.transactions.index', ['category' => $fuel->transaction_category_id]))
            ->assertOk()
            ->assertSee('February fuel cost')
            ->assertDontSee('January salary income');

        $this->actingAs($staff)
            ->get(route('admin.transactions.index', ['from' => '2026-02-01', 'to' => '2026-02-28']))
            ->assertOk()
            ->assertSee('February office rent')
            ->assertSee('February fuel cost')
            ->assertDontSee('January salary income');
    }

    public function test_staff_without_permission_cannot_manage_transactions(): void
    {
        $staff = $this->createStaffUser();
        $transaction = Transaction::factory()->create();

        $this->actingAs($staff)->get(route('admin.transactions.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.transactions.store'), [])->assertForbidden();
        $this->actingAs($staff)->put(route('admin.transactions.update', $transaction), [])->assertForbidden();
        $this->actingAs($staff)->delete(route('admin.transactions.destroy', $transaction))->assertForbidden();
    }
}
