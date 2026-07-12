<?php

namespace App\Models;

use App\Enums\TtAccountStatus;
use Database\Factories\TtAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'title', 'currency_id', 'opening_balance', 'status'])]
class TtAccount extends Model
{
    /** @use HasFactory<TtAccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'status' => TtAccountStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Statement entries in insertion (id) order — many rows carry no date,
     * so the running balance always follows the order entries were recorded.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TtAccountEntry::class)->orderBy('id');
    }
}
