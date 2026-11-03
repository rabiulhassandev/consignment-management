<?php

use App\Enums\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('invoice_no')
                ->constrained('users')->cascadeOnDelete();
        });

        $this->linkLegacyInvoicesToCustomers();

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bill_to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->restoreLegacyBillToNames();

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bill_to')->nullable(false)->change();
        });
    }

    /**
     * Point legacy invoices at the customer whose name or company name matches the
     * free-text bill-to value. Unmatched invoices keep rendering from `bill_to`.
     */
    private function linkLegacyInvoicesToCustomers(): void
    {
        $customers = DB::table('users')
            ->where('type', UserType::Customer->value)
            ->orderBy('id')
            ->get(['id', 'name', 'company_name']);

        foreach ($customers as $customer) {
            $names = array_values(array_filter([$customer->name, $customer->company_name]));

            if ($names === []) {
                continue;
            }

            DB::table('invoices')
                ->whereNull('customer_id')
                ->whereIn('bill_to', $names)
                ->update(['customer_id' => $customer->id]);
        }
    }

    /**
     * Refill `bill_to` from the linked customer so the column can go back to NOT NULL.
     */
    private function restoreLegacyBillToNames(): void
    {
        DB::table('invoices')
            ->whereNull('bill_to')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->each(function (object $invoice): void {
                $name = DB::table('users')->where('id', $invoice->customer_id)->value('name');

                DB::table('invoices')->where('id', $invoice->id)->update(['bill_to' => $name ?? '']);
            });

        DB::table('invoices')->whereNull('bill_to')->update(['bill_to' => '']);
    }
};
