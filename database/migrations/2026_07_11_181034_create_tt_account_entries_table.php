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
        Schema::create('tt_account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_account_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date')->nullable();
            $table->string('description');
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->foreignId('source_currency_id')->nullable()->constrained('currencies')->restrictOnDelete();
            $table->decimal('source_amount', 15, 2)->nullable();
            $table->decimal('source_rate', 10, 4)->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_account_entries');
    }
};
