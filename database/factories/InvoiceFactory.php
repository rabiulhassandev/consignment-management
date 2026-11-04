<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_no' => fake()->unique()->numerify('INV-####'),
            'customer_id' => User::factory()->customer(),
            'bill_to' => null,
            'bill_to_address' => fake()->address(),
            'invoice_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'currency_id' => Currency::factory(),
        ];
    }

    /**
     * An invoice as created before customers were selectable: a free-text bill-to name
     * and no linked customer.
     */
    public function legacy(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
            'bill_to' => fake()->company(),
        ]);
    }
}
