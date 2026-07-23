<?php

namespace App\Models;

use App\Models\Concerns\SpellsCurrencyAmount;
use Database\Factories\SalesContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'contract_no',
    'buyer',
    'buyer_address',
    'contract_date',
    'currency_id',
    'freight_charge',
    'terms',
])]
class SalesContract extends Model
{
    /** @use HasFactory<SalesContractFactory> */
    use HasFactory;

    use SpellsCurrencyAmount;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_date' => 'date',
            'freight_charge' => 'decimal:2',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesContractItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Sum of the line items, excluding freight (requires loaded items).
     */
    public function itemsTotal(): float
    {
        return round((float) $this->items->sum('amount'), 2);
    }

    /**
     * Contract value: line items plus the freight charge.
     */
    public function totalAmount(): float
    {
        return round($this->itemsTotal() + (float) $this->freight_charge, 2);
    }

    /**
     * The terms split into the numbered clauses printed on the contract.
     *
     * @return Collection<int, string>
     */
    public function termLines(): Collection
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->terms))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();
    }

    /**
     * The contract value spelled out, as printed on the contract ("... Yuan Only").
     */
    public function amountInWords(): string
    {
        return $this->spellAmount($this->totalAmount());
    }
}
