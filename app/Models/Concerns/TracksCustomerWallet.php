<?php

namespace App\Models\Concerns;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Derives the customer wallet balance from invoices and the amounts collected against
 * them: balance = total received − total invoiced.
 *
 * Balances are kept per currency, the same way a TT account holds a single currency —
 * an invoice raised in CNY and a collection received in BDT never mix into one figure,
 * so no exchange rate is ever assumed. Nothing is stored: like the TT account statement,
 * the running balance is computed on read, so it can never drift out of step with the
 * invoices it is built from.
 */
trait TracksCustomerWallet
{
    /**
     * Received and invoiced totals over the matched invoices.
     */
    private const WALLET_TOTALS_SELECT = 'COALESCE(SUM(invoices.received_amount), 0) AS received_total, '
        .'COALESCE(SUM(item_totals.invoiced), 0) AS invoiced_total';

    private ?float $openingWalletBalance = null;

    /**
     * Wallet balances for every customer, keyed by customer id then currency id.
     * Pass an invoice to leave it out — used by the edit form, which must show the
     * balance as it stood before the invoice being edited.
     *
     * @return array<int, array<int, float>>
     */
    public static function walletBalancesByCustomer(?self $except = null): array
    {
        $rows = self::walletQuery()
            ->when(
                $except !== null && $except->exists,
                fn (Builder $query) => $query->whereKeyNot($except->getKey()),
            )
            ->groupBy('invoices.customer_id', 'invoices.currency_id')
            ->selectRaw('invoices.customer_id, invoices.currency_id')
            ->selectRaw(self::WALLET_TOTALS_SELECT)
            ->get();

        $balances = [];

        foreach ($rows as $row) {
            $balances[(int) $row->customer_id][(int) $row->currency_id] = round(
                (float) $row->received_total - (float) $row->invoiced_total,
                2,
            );
        }

        return $balances;
    }

    /**
     * The customer's balance in this invoice's currency before this invoice is applied,
     * counting only invoices that sort ahead of it by date then id.
     */
    public function openingWalletBalance(): float
    {
        if ($this->customer_id === null) {
            return 0.0;
        }

        if ($this->openingWalletBalance !== null) {
            return $this->openingWalletBalance;
        }

        $invoiceDate = $this->invoice_date->toDateString();

        $totals = self::walletQuery()
            ->where('invoices.customer_id', $this->customer_id)
            ->where('invoices.currency_id', $this->currency_id)
            ->where(fn (Builder $query) => $query
                ->where('invoices.invoice_date', '<', $invoiceDate)
                ->orWhere(fn (Builder $query) => $query
                    ->where('invoices.invoice_date', '=', $invoiceDate)
                    ->where('invoices.id', '<', $this->id)))
            ->selectRaw(self::WALLET_TOTALS_SELECT)
            ->first();

        return $this->openingWalletBalance = round(
            (float) $totals->received_total - (float) $totals->invoiced_total,
            2,
        );
    }

    /**
     * The customer's balance in this invoice's currency after this invoice is applied.
     */
    public function closingWalletBalance(): float
    {
        return round($this->openingWalletBalance() + $this->receivedAmount() - $this->totalAmount(), 2);
    }

    /**
     * The amount collected against this invoice, in the invoice currency.
     */
    public function receivedAmount(): float
    {
        return round((float) $this->received_amount, 2);
    }

    /**
     * Whether this invoice has anything to say about the customer's wallet — a
     * collection of its own, or a balance carried in from earlier invoices.
     */
    public function hasWalletActivity(): bool
    {
        return $this->customer_id !== null
            && ($this->receivedAmount() !== 0.0 || $this->openingWalletBalance() !== 0.0);
    }

    /**
     * Invoices joined to their line-item totals, one row per invoice so the received
     * amounts cannot fan out across items.
     */
    private static function walletQuery(): Builder
    {
        return self::query()
            ->leftJoinSub(
                InvoiceItem::query()->selectRaw('invoice_id, SUM(amount) AS invoiced')->groupBy('invoice_id'),
                'item_totals',
                'item_totals.invoice_id',
                '=',
                'invoices.id',
            )
            ->whereNotNull('invoices.customer_id');
    }
}
