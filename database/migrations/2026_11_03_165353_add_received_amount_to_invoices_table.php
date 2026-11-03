<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The amount collected from the customer against this invoice, in the invoice
     * currency. The customer wallet balance is derived from these amounts and the
     * invoice totals, so existing invoices start at 0 and change nothing.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('received_amount', 15, 2)->default(0)->after('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('received_amount');
        });
    }
};
