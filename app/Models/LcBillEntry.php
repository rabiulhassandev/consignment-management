<?php

namespace App\Models;

use App\Enums\EntryType;
use Database\Factories\LcBillEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lc_bill_id',
    'type',
    'entry_date',
    'description',
    'source_amount',
    'source_rate',
    'amount',
    'sort_order',
])]
class LcBillEntry extends Model
{
    /** @use HasFactory<LcBillEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EntryType::class,
            'entry_date' => 'date',
            'source_amount' => 'decimal:2',
            'source_rate' => 'decimal:4',
            'amount' => 'decimal:2',
        ];
    }

    public function lcBill(): BelongsTo
    {
        return $this->belongsTo(LcBill::class);
    }
}
