<?php

namespace Database\Factories;

use App\Enums\ConversionOperation;
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
            'conversion_currency_id' => null,
            'conversion_operation' => ConversionOperation::Multiply,
            'is_settled' => false,
        ];
    }

    /**
     * Convert the balance into the given currency at the given rate.
     */
    public function convertedTo(Currency $currency, string $rate, ConversionOperation $operation = ConversionOperation::Multiply): static
    {
        return $this->state(fn (array $attributes) => [
            'conversion_currency_id' => $currency->id,
            'conversion_rate' => $rate,
            'conversion_operation' => $operation,
        ]);
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
