<?php

namespace App\Models;

use App\Enums\ConversionOperation;
use App\Enums\EntryType;
use Database\Factories\LcBillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id',
    'bill_no',
    'bill_date',
    'lc_number',
    'lc_value',
    'ci_value',
    'shipment_title',
    'currency_id',
    'conversion_rate',
    'conversion_currency_id',
    'conversion_operation',
    'is_settled',
])]
class LcBill extends Model
{
    /** @use HasFactory<LcBillFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'lc_value' => 'decimal:2',
            'ci_value' => 'decimal:2',
            'conversion_rate' => 'decimal:4',
            'conversion_operation' => ConversionOperation::class,
            'is_settled' => 'boolean',
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
     * The currency the closing balance is converted into.
     */
    public function conversionCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'conversion_currency_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LcBillEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Sum of received entries, in the bill currency (requires loaded entries).
     */
    public function totalReceived(): float
    {
        return (float) $this->entries->where('type', EntryType::Received)->sum('amount');
    }

    /**
     * Sum of paid/expense entries, in the bill currency (requires loaded entries).
     */
    public function totalPaid(): float
    {
        return (float) $this->entries->where('type', EntryType::Paid)->sum('amount');
    }

    /**
     * Received minus paid, in the bill currency.
     */
    public function balance(): float
    {
        return round($this->totalReceived() - $this->totalPaid(), 2);
    }

    /**
     * The balance converted into the settlement currency, or null when no rate is set.
     */
    public function localDue(): ?float
    {
        $rate = (float) $this->conversion_rate;

        if ($this->conversion_rate === null || $rate <= 0) {
            return null;
        }

        return round($this->conversionOperation()->apply($this->balance(), $rate), 2);
    }

    /**
     * The operation used to convert the balance, defaulting to multiplication.
     */
    public function conversionOperation(): ConversionOperation
    {
        return $this->conversion_operation ?? ConversionOperation::Multiply;
    }

    /**
     * Currency code shown alongside the converted balance.
     */
    public function conversionCurrencyCode(): string
    {
        return $this->conversionCurrency?->code ?? 'BDT';
    }

    /**
     * Currency symbol shown alongside the converted balance.
     */
    public function conversionCurrencySymbol(): string
    {
        return $this->conversionCurrency?->symbol ?? '৳';
    }
}
