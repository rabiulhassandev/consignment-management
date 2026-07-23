<?php

namespace Database\Factories;

use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProformaInvoiceItem>
 */
class ProformaInvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $rate = fake()->randomFloat(2, 1, 500);

        return [
            'proforma_invoice_id' => ProformaInvoice::factory(),
            'description' => fake()->words(3, true),
            'hs_code' => fake()->numerify('####.##.##'),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['SETS', 'PCS', 'KGS', 'CTNS']),
            'rate' => $rate,
            'amount' => round($quantity * $rate, 2),
            'sort_order' => 0,
        ];
    }
}
