<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\LcBill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LcBill>
 */
class LcBillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->customer(),
            'bill_no' => fake()->unique()->numerify('LCB-####'),
            'bill_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'lc_number' => fake()->unique()->numerify('############'),
            'lc_value' => fake()->randomFloat(2, 1000, 100000),
            'ci_value' => fake()->randomFloat(2, 1000, 100000),
            'shipment_title' => strtoupper(fake()->words(3, true)),
            'currency_id' => Currency::factory(),
            'conversion_rate' => null,
            'is_settled' => false,
        ];
    }

    /**
     * Mark the bill as settled.
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_settled' => true,
        ]);
    }
}
