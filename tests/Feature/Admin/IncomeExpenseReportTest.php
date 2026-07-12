<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class IncomeExpenseReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_overview_shows_income_expense_and_cash_totals(): void
    {
        $staff = $this->createStaffUser('transactions.view');
        $today = now()->toDateString();

        Transaction::factory()->income()->onDate($today)->create(['amount' => '1000.00']);
        Transaction::factory()->income()->onDate($today)->create(['amount' => '250.00']);
        Transaction::factory()->expense()->onDate($today)->create(['amount' => '400.00']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.index'))
            ->assertOk()
            ->assertSee('1,250.00')
            ->assertSee('400.00')
            ->assertSee('850.00');
    }

    public function test_monthly_report_groups_by_day_with_correct_totals(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-03-05')->create(['amount' => '100.25']);
        Transaction::factory()->expense()->onDate('2026-03-05')->create(['amount' => '40.00']);
        Transaction::factory()->income()->onDate('2026-03-20')->create(['amount' => '60.00']);
        Transaction::factory()->income()->onDate('2026-04-02')->create(['amount' => '999.00']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', ['period' => 'monthly', 'month' => '2026-03']))
            ->assertOk()
            ->assertSee('05 Mar 2026')
            ->assertSee('20 Mar 2026')
            ->assertSee('160.25')
            ->assertSee('120.25')
            ->assertDontSee('999.00')
            ->assertDontSee('02 Apr 2026');
    }

    public function test_yearly_report_groups_by_month(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2025-01-15')->create(['amount' => '100.00']);
        Transaction::factory()->expense()->onDate('2025-03-10')->create(['amount' => '30.00']);
        Transaction::factory()->income()->onDate('2024-06-01')->create(['amount' => '500.00']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', ['period' => 'yearly', 'year' => 2025]))
            ->assertOk()
            ->assertSee('Jan 2025')
            ->assertSee('Mar 2025')
            ->assertSee('70.00')
            ->assertDontSee('500.00')
            ->assertDontSee('Jun 2024');
    }

    public function test_daily_report_lists_entries_for_selected_date_only(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-05-05')->create(['amount' => '90.00', 'description' => 'Machine sale proceeds']);
        Transaction::factory()->expense()->onDate('2026-05-05')->create(['amount' => '25.00', 'description' => 'Fuel cost']);
        Transaction::factory()->income()->onDate('2026-05-06')->create(['description' => 'Unrelated next-day entry']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', ['period' => 'daily', 'date' => '2026-05-05']))
            ->assertOk()
            ->assertSee('Machine sale proceeds')
            ->assertSee('Fuel cost')
            ->assertSee('65.00')
            ->assertDontSee('Unrelated next-day entry');
    }

    public function test_report_print_view_renders(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-03-05')->create(['amount' => '100.25']);
        Transaction::factory()->expense()->onDate('2026-03-08')->create(['amount' => '40.00']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report.print', ['period' => 'monthly', 'month' => '2026-03']))
            ->assertOk()
            ->assertSee('Income & Expense Report')
            ->assertSee('March 2026')
            ->assertSee('100.25')
            ->assertSee('60.25');
    }

    public function test_invalid_report_period_is_rejected(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', ['period' => 'weekly']))
            ->assertSessionHasErrors('period');
    }

    public function test_staff_without_permission_cannot_view_overview_or_reports(): void
    {
        $staff = $this->createStaffUser();

        $this->actingAs($staff)->get(route('admin.income-expense.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.income-expense.report'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.income-expense.report.print'))->assertForbidden();
    }
}
