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
        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('hs_code', 100)->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('rate', 12, 2)->nullable();
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
