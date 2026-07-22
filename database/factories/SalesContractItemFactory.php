<?php

namespace Database\Factories;

use App\Models\SalesContract;
use App\Models\SalesContractItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesContractItem>
 */
class SalesContractItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $unitPrice = fake()->randomFloat(2, 1, 500);

        return [
            'sales_contract_id' => SalesContract::factory(),
            'description' => fake()->words(3, true),
            'hs_code' => fake()->numerify('####.##.##'),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['SETS', 'PCS', 'KGS', 'CTNS']),
            'unit_price' => $unitPrice,
            'amount' => round($quantity * $unitPrice, 2),
            'sort_order' => 0,
        ];
    }
}
