<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\LcBill;
use App\Models\LcBillEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LcBillEntry>
 */
class LcBillEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lc_bill_id' => LcBill::factory(),
            'type' => EntryType::Paid,
            'entry_date' => null,
            'description' => fake()->words(3, true),
            'source_amount' => null,
            'source_rate' => null,
            'amount' => fake()->randomFloat(2, 50, 10000),
            'sort_order' => 0,
        ];
    }

    /**
     * A money-received entry.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Received,
        ]);
    }

    /**
     * A paid/expense entry.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Paid,
        ]);
    }
}
