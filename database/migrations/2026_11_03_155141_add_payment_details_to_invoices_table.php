<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-invoice payment details. Invoices left with all six columns null keep
     * printing the site-wide defaults from settings.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('currency_id');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number', 100)->nullable()->after('bank_account_name');
            $table->string('bank_branch')->nullable()->after('bank_account_number');
            $table->string('bank_swift_code', 50)->nullable()->after('bank_branch');
            $table->string('bank_routing_number', 50)->nullable()->after('bank_swift_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_branch',
                'bank_swift_code',
                'bank_routing_number',
            ]);
        });
    }
};
