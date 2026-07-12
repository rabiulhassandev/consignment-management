<?php

namespace Database\Factories;

use App\Enums\TtAccountStatus;
use App\Models\Currency;
use App\Models\TtAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TtAccount>
 */
class TtAccountFactory extends Factory
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
            'title' => strtoupper(fake()->company()).' TT ACCOUNTS '.now()->year,
            'currency_id' => Currency::factory(),
            'opening_balance' => null,
            'status' => TtAccountStatus::Open,
        ];
    }

    /**
     * Mark the account as closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TtAccountStatus::Closed,
        ]);
    }
}
