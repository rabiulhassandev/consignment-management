<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            ->assertDontSee('Jun 2024');
    }

    public function test_yearly_report_carries_earlier_transactions_as_the_opening_balance(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2024-06-01')->create(['amount' => '500.00']);
        Transaction::factory()->income()->onDate('2025-01-15')->create(['amount' => '100.00']);
        Transaction::factory()->expense()->onDate('2025-03-10')->create(['amount' => '30.00']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', ['period' => 'yearly', 'year' => 2025]))
            ->assertOk()
            ->assertSee('Opening balance')
            ->assertSee('500.00')
            ->assertSee('570.00');
    }

    public function test_custom_range_report_lists_entries_between_the_two_dates(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-02-01')->create(['amount' => '80.00', 'description' => 'Before the range']);
        Transaction::factory()->income()->onDate('2026-02-10')->create(['amount' => '300.00', 'description' => 'Container clearance']);
        Transaction::factory()->expense()->onDate('2026-02-18')->create(['amount' => '120.00', 'description' => 'Warehouse rent']);
        Transaction::factory()->expense()->onDate('2026-03-05')->create(['amount' => '999.00', 'description' => 'After the range']);

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', [
                'period' => 'range',
                'date_from' => '2026-02-05',
                'date_to' => '2026-02-20',
            ]))
            ->assertOk()
            ->assertSee('05 Feb 2026 — 20 Feb 2026')
            ->assertSee('Container clearance')
            ->assertSee('Warehouse rent')
            ->assertSee('180.00')
            ->assertSee('260.00')
            ->assertDontSee('Before the range')
            ->assertDontSee('After the range');
    }

    public function test_custom_range_report_rejects_an_end_date_before_the_start_date(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        $this->actingAs($staff)
            ->get(route('admin.income-expense.report', [
                'period' => 'range',
                'date_from' => '2026-02-20',
                'date_to' => '2026-02-05',
            ]))
            ->assertSessionHasErrors('date_to');
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
            ->assertSee('Income & Expense Statement')
            ->assertSee('March 2026')
            ->assertSee('100.25')
            ->assertSee('60.25');
    }

    public function test_report_pdf_download_is_generated(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-03-05')->create(['amount' => '100.25']);

        $response = $this->actingAs($staff)
            ->get(route('admin.income-expense.report.pdf', ['period' => 'monthly', 'month' => '2026-03']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'income-expense-statement-march-2026.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_report_excel_download_contains_the_statement_lines_and_balances(): void
    {
        $staff = $this->createStaffUser('transactions.view');

        Transaction::factory()->income()->onDate('2026-01-20')->create(['amount' => '200.00', 'description' => 'Brought forward sale']);
        Transaction::factory()->income()->onDate('2026-02-10')->create(['amount' => '300.00', 'description' => 'Container clearance']);
        Transaction::factory()->expense()->onDate('2026-02-18')->create(['amount' => '120.00', 'description' => 'Warehouse rent']);

        $response = $this->actingAs($staff)->get(route('admin.income-expense.report.excel', [
            'period' => 'range',
            'date_from' => '2026-02-01',
            'date_to' => '2026-02-28',
        ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $this->assertStringContainsString(
            'income-expense-statement-01-feb-2026-28-feb-2026.xlsx',
            $response->headers->get('content-disposition'),
        );

        $values = $this->workbookValues($response->streamedContent());

        $this->assertContains('Opening balance', $values);
        $this->assertContains('Container clearance', $values);
        $this->assertContains('Warehouse rent', $values);
        $this->assertContains('CLOSING BALANCE', $values);
        $this->assertContains('200', $values, 'The opening balance carries the January income forward.');
        $this->assertContains('380', $values, 'The closing balance is opening + income - expense.');
        $this->assertNotContains('Brought forward sale', $values);
    }

    /**
     * Flatten every non-empty cell of a generated workbook into a list of string values.
     *
     * @return array<int, string>
     */
    private function workbookValues(string $contents): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'income-expense-excel-test-');
        file_put_contents($tempFile, $contents);

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
        $this->actingAs($staff)->get(route('admin.income-expense.report.pdf'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.income-expense.report.excel'))->assertForbidden();
    }
}
