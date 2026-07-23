<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->date('invoice_date')->index();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();

            $table->string('exporter_name')->nullable();
            $table->string('exporter_address', 500)->nullable();
            $table->string('buyer_name');
            $table->string('buyer_address', 500)->nullable();

            $table->string('advising_bank_name')->nullable();
            $table->string('advising_bank_address', 500)->nullable();
            $table->string('advising_bank_swift', 50)->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_account', 100)->nullable();

            $table->string('pre_carriage', 150)->nullable();
            $table->string('place_of_receipt', 150)->nullable();
            $table->string('country_of_origin', 150)->nullable();
            $table->string('port_of_loading', 150)->nullable();
            $table->string('port_of_discharge', 150)->nullable();
            $table->string('final_destination', 150)->nullable();

            $table->string('delivery_payment_terms')->nullable();
            $table->string('incoterm', 20)->nullable();
            $table->string('mark', 100)->nullable();
            $table->string('declaration', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};
