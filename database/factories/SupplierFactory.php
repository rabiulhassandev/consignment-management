<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
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
            'category_id' => Category::factory(),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'wechat' => fake()->userName(),
            'address' => fake()->address(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
