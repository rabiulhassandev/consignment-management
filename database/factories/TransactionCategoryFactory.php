<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionCategory>
 */
class TransactionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => TransactionType::Expense,
        ];
    }

    /**
     * An income category.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Income,
        ]);
    }

    /**
     * An expense category.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Expense,
        ]);
    }
}
