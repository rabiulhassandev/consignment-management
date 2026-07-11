<?php

namespace Database\Factories;

use App\Models\Consignment;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consignment>
 */
class ConsignmentFactory extends Factory
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
            'consignment_no' => fake()->unique()->numerify('CN-####'),
            'consignment_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'currency_id' => Currency::factory(),
        ];
    }
}
