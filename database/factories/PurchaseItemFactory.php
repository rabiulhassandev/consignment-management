<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Consignment;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'consignment_id' => Consignment::factory(),
            'purchase_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'product_name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'sample_number' => fake()->unique()->bothify('SMP-####??'),
            'own_sample_number' => fake()->unique()->bothify('OWN-####??'),
            'amount' => fake()->randomFloat(2, 100, 50000),
        ];
    }

    /**
     * Keep the item's supplier and category consistent with its consignment's customer.
     */
    public function forConsignment(Consignment $consignment): static
    {
        return $this->state(fn (array $attributes) => [
            'consignment_id' => $consignment->id,
            'supplier_id' => Supplier::factory()->for($consignment->customer, 'customer'),
        ]);
    }
}
