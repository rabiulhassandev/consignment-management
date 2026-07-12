<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 500);
        $rate = fake()->randomFloat(2, 10, 1000);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->words(3, true),
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => round($quantity * $rate, 2),
            'sort_order' => 0,
        ];
    }

    /**
     * An amount-only line without quantity and rate (e.g. a delivery fee).
     */
    public function amountOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => null,
            'rate' => null,
            'amount' => fake()->randomFloat(2, 100, 5000),
        ]);
    }
}
