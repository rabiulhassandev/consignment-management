<?php

namespace App\Models;

use Database\Factories\PurchaseItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'consignment_id',
    'purchase_date',
    'product_name',
    'category_id',
    'supplier_id',
    'sample_number',
    'own_sample_number',
    'amount',
])]
class PurchaseItem extends Model
{
    /** @use HasFactory<PurchaseItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
