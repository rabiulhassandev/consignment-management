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
        Schema::create('sales_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_no')->unique();
            $table->string('buyer');
            $table->string('buyer_address', 500)->nullable();
            $table->date('contract_date')->index();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('freight_charge', 15, 2)->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_contracts');
    }
};
