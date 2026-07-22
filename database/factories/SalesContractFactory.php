<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\SalesContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesContract>
 */
class SalesContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_no' => fake()->unique()->numerify('SC-####'),
            'buyer' => fake()->company(),
            'buyer_address' => fake()->address(),
            'contract_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'currency_id' => Currency::factory(),
            'freight_charge' => fake()->randomFloat(2, 100, 2000),
            'terms' => "THE PRICE IS BASED ON EXW\n100% RMB TT IN ADVANCE",
        ];
    }

    /**
     * A contract carrying no separate freight charge.
     */
    public function withoutFreight(): static
    {
        return $this->state(fn (array $attributes) => [
            'freight_charge' => null,
        ]);
    }
}
