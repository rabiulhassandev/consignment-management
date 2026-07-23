<?php

namespace App\Models;

use Database\Factories\ProformaInvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'proforma_invoice_id',
    'description',
    'hs_code',
    'quantity',
    'unit',
    'rate',
    'amount',
    'sort_order',
])]
class ProformaInvoiceItem extends Model
{
    /** @use HasFactory<ProformaInvoiceItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function proformaInvoice(): BelongsTo
    {
        return $this->belongsTo(ProformaInvoice::class);
    }

    /**
     * Quantity and unit as printed in the single "Quantity" column ("600 SETS").
     */
    public function quantityLabel(): string
    {
        if ($this->quantity === null) {
            return trim((string) $this->unit);
        }

        $quantity = rtrim(rtrim(number_format((float) $this->quantity, 2), '0'), '.');

        return trim($quantity.' '.(string) $this->unit);
    }
}
