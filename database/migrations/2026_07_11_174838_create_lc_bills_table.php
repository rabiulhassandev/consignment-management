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
        Schema::create('lc_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('bill_no')->unique();
            $table->date('bill_date')->index();
            $table->string('lc_number')->index();
            $table->decimal('lc_value', 15, 2)->nullable();
            $table->decimal('ci_value', 15, 2)->nullable();
            $table->string('shipment_title')->nullable();
            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->decimal('conversion_rate', 10, 4)->nullable();
            $table->boolean('is_settled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lc_bills');
    }
};
