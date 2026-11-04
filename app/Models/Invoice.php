<?php

namespace App\Models;

use App\Models\Concerns\SpellsCurrencyAmount;
use App\Models\Concerns\TracksCustomerWallet;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'invoice_no',
    'customer_id',
    'bill_to',
    'bill_to_address',
    'invoice_date',
    'currency_id',
    'received_amount',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'bank_branch',
    'bank_swift_code',
    'bank_routing_number',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use SpellsCurrencyAmount;
    use TracksCustomerWallet;

    /**
     * The payment detail fields, mapped to the labels printed on the document.
     *
     * @var array<string, string>
     */
    public const PAYMENT_DETAIL_LABELS = [
        'bank_name' => 'Bank',
        'bank_account_name' => 'Account Name',
        'bank_account_number' => 'Account No.',
        'bank_branch' => 'Branch',
        'bank_swift_code' => 'SWIFT / BIC',
        'bank_routing_number' => 'Routing No.',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'received_amount' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The billed party: the linked customer, falling back to the free-text name
     * kept on invoices created before customers were selectable.
     */
    public function billToName(): string
    {
        return $this->customer?->name ?? (string) $this->bill_to;
    }

    /**
     * The billed address: the per-invoice override, falling back to the customer's address.
     */
    public function billToAddress(): ?string
    {
        return $this->bill_to_address ?: $this->customer?->address;
    }

    /**
     * Invoice value: the sum of the line items (requires loaded items).
     */
    public function totalAmount(): float
    {
        return round((float) $this->items->sum('amount'), 2);
    }

    /**
     * The invoice total spelled out for the "In words" line, e.g. "Ten Thousand (CNY) Only".
     */
    public function amountInWords(): string
    {
        return $this->spellAmountWords($this->totalAmount()).' ('.$this->currency->code.') Only';
    }

    /**
     * The site-wide payment details that new invoices are prefilled with. The columns
     * are named after the matching settings keys.
     *
     * @return array<string, string|null>
     */
    public static function defaultPaymentDetails(): array
    {
        $defaults = [];

        foreach (array_keys(self::PAYMENT_DETAIL_LABELS) as $field) {
            $defaults[$field] = Setting::get($field);
        }

        return $defaults;
    }

    /**
     * Whether this invoice carries payment details of its own instead of inheriting
     * the site defaults.
     */
    public function hasOwnPaymentDetails(): bool
    {
        foreach (array_keys(self::PAYMENT_DETAIL_LABELS) as $field) {
            if (filled($this->{$field})) {
                return true;
            }
        }

        return false;
    }

    /**
     * This invoice's payment details: the values captured on the invoice itself, or the
     * site defaults when none were captured. Treated as a set — an invoice that carries
     * any detail of its own is used verbatim, so a field the admin deliberately cleared
     * stays cleared instead of falling back to settings.
     *
     * @return array<string, string|null>
     */
    public function paymentDetails(): array
    {
        if (! $this->hasOwnPaymentDetails()) {
            return self::defaultPaymentDetails();
        }

        $details = [];

        foreach (array_keys(self::PAYMENT_DETAIL_LABELS) as $field) {
            $details[$field] = $this->{$field};
        }

        return $details;
    }

    /**
     * Payment details as printed label/value pairs, with empty fields dropped.
     *
     * @return array<string, string>
     */
    public function printedPaymentDetails(): array
    {
        $details = $this->paymentDetails();
        $printed = [];

        foreach (self::PAYMENT_DETAIL_LABELS as $field => $label) {
            if (filled($details[$field] ?? null)) {
                $printed[$label] = (string) $details[$field];
            }
        }

        return $printed;
    }
}
