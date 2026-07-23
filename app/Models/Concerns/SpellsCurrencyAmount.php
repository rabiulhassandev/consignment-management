<?php

namespace App\Models\Concerns;

use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Spells document totals out in words for the "in words" / "say" line printed on
 * trade documents. Requires the model to expose a `currency` relation.
 */
trait SpellsCurrencyAmount
{
    /**
     * Spell an amount out in words, suffixed with the document currency ("... Yuan Only").
     */
    protected function spellAmount(float $amount): string
    {
        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);

        $words = Str::title(str_replace('-', ' ', Number::spell($whole)));

        if ($fraction > 0) {
            $words .= ' and '.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT).'/100';
        }

        return trim($words.' '.$this->currency->name).' Only';
    }
}
