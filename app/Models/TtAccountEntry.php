<?php

namespace App\Models;

use App\Enums\ConversionOperation;
use App\Enums\EntryType;
use Database\Factories\TtAccountEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tt_account_id',
    'entry_date',
    'description',
    'type',
    'amount',
    'source_currency_id',
    'source_amount',
    'source_rate',
    'source_operation',
    'remarks',
])]
class TtAccountEntry extends Model
{
    /** @use HasFactory<TtAccountEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'type' => EntryType::class,
            'amount' => 'decimal:2',
            'source_amount' => 'decimal:2',
            'source_rate' => 'decimal:4',
            'source_operation' => ConversionOperation::class,
        ];
    }

    public function ttAccount(): BelongsTo
    {
        return $this->belongsTo(TtAccount::class);
    }

    /**
     * The operation used to convert the source amount at the source rate.
     */
    public function sourceOperation(): ConversionOperation
    {
        return $this->source_operation ?? ConversionOperation::Multiply;
    }

    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'source_currency_id');
    }
}
