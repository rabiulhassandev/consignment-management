<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Invoice;
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
            'bill_to' => fake()->company(),
            'bill_to_address' => fake()->address(),
            'invoice_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'currency_id' => Currency::factory(),
        ];
    }
}
