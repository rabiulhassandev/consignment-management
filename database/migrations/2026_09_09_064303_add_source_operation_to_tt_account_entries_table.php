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
        Schema::table('tt_account_entries', function (Blueprint $table) {
            $table->string('source_operation', 20)->default(ConversionOperation::Multiply->value)->after('source_rate');
        });

        // Entries recorded before this column existed were always converted by
        // dividing the source amount by the rate — keep their history accurate.
        DB::table('tt_account_entries')
            ->whereNotNull('source_rate')
            ->update(['source_operation' => ConversionOperation::Divide->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_account_entries', function (Blueprint $table) {
            $table->dropColumn('source_operation');
        });
    }
};
