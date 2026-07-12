<?php

namespace Database\Factories;

use App\Enums\EntryType;
use App\Models\Currency;
use App\Models\TtAccount;
use App\Models\TtAccountEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TtAccountEntry>
 */
class TtAccountEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tt_account_id' => TtAccount::factory(),
            'entry_date' => null,
            'description' => fake()->words(3, true),
            'type' => EntryType::Paid,
            'amount' => fake()->randomFloat(2, 50, 10000),
            'source_currency_id' => null,
            'source_amount' => null,
            'source_rate' => null,
            'remarks' => null,
        ];
    }

    /**
     * A money-received (credit) entry.
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Received,
        ]);
    }

    /**
     * A paid (debit) entry.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Paid,
        ]);
    }

    /**
     * A receipt converted from a source currency at a rate.
     */
    public function converted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => EntryType::Received,
            'source_currency_id' => Currency::factory(),
            'source_amount' => '134810.00',
            'source_rate' => '18',
            'amount' => '7489.44',
        ]);
    }
}
