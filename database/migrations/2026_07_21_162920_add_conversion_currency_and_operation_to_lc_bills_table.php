<?php

use App\Enums\ConversionOperation;
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
        Schema::table('lc_bills', function (Blueprint $table) {
            $table->foreignId('conversion_currency_id')->nullable()->after('conversion_rate')->constrained('currencies')->nullOnDelete();
            $table->string('conversion_operation', 20)->default(ConversionOperation::Multiply->value)->after('conversion_currency_id');
        });

        $takaId = DB::table('currencies')->where('code', 'BDT')->value('id');

        if ($takaId !== null) {
            DB::table('lc_bills')
                ->whereNotNull('conversion_rate')
                ->update(['conversion_currency_id' => $takaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lc_bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversion_currency_id');
            $table->dropColumn('conversion_operation');
        });
    }
};
