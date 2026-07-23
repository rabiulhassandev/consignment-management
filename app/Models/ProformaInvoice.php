<?php

namespace App\Models;

use App\Models\Concerns\SpellsCurrencyAmount;
use Database\Factories\ProformaInvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_no',
    'invoice_date',
    'currency_id',
    'exporter_name',
    'exporter_address',
    'buyer_name',
    'buyer_address',
    'advising_bank_name',
    'advising_bank_address',
    'advising_bank_swift',
    'beneficiary_name',
    'beneficiary_account',
    'pre_carriage',
    'place_of_receipt',
    'country_of_origin',
    'port_of_loading',
    'port_of_discharge',
    'final_destination',
    'delivery_payment_terms',
    'incoterm',
    'mark',
    'declaration',
])]
class ProformaInvoice extends Model
{
    /** @use HasFactory<ProformaInvoiceFactory> */
    use HasFactory;

    use SpellsCurrencyAmount;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Invoice value: the sum of the line items (requires loaded items).
     */
    public function totalAmount(): float
    {
        return round((float) $this->items->sum('amount'), 2);
    }

    /**
     * The invoice value spelled out for the "TOTAL : SAY" line.
     */
    public function amountInWords(): string
    {
        return $this->spellAmount($this->totalAmount());
    }

    /**
     * Whether any advising bank detail was captured for this invoice.
     */
    public function hasAdvisingBankDetails(): bool
    {
        return (bool) ($this->advising_bank_name
            || $this->advising_bank_address
            || $this->advising_bank_swift
            || $this->beneficiary_name
            || $this->beneficiary_account);
    }

    /**
     * Whether any shipping/routing detail was captured for this invoice.
     */
    public function hasShippingDetails(): bool
    {
        return (bool) ($this->pre_carriage
            || $this->place_of_receipt
            || $this->country_of_origin
            || $this->port_of_loading
            || $this->port_of_discharge
            || $this->final_destination);
    }
}
