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
        Schema::create('lc_bill_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lc_bill_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->date('entry_date')->nullable();
            $table->string('description');
            $table->decimal('source_amount', 15, 2)->nullable();
            $table->decimal('source_rate', 10, 4)->nullable();
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
        Schema::dropIfExists('lc_bill_entries');
    }
};
