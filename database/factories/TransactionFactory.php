<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_category_id' => TransactionCategory::factory()->expense(),
            'type' => TransactionType::Expense,
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'description' => fake()->sentence(3),
        ];
    }

    /**
     * An income entry with a matching income category.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_category_id' => TransactionCategory::factory()->income(),
            'type' => TransactionType::Income,
        ]);
    }

    /**
     * An expense entry with a matching expense category.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_category_id' => TransactionCategory::factory()->expense(),
            'type' => TransactionType::Expense,
        ]);
    }

    /**
     * An entry recorded on a specific date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => $date,
        ]);
    }
}
