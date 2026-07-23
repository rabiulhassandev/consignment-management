<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ProformaInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProformaInvoice>
 */
class ProformaInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_no' => fake()->unique()->numerify('PI-####'),
            'invoice_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'currency_id' => Currency::factory(),
            'exporter_name' => fake()->company(),
            'exporter_address' => fake()->address(),
            'buyer_name' => fake()->company(),
            'buyer_address' => fake()->address(),
            'advising_bank_name' => 'HSBC BANK(CHINA) COMPANY LIMITED',
            'advising_bank_address' => fake()->address(),
            'advising_bank_swift' => 'HSBCCNSHGZH',
            'beneficiary_name' => fake()->company(),
            'beneficiary_account' => fake()->numerify('###-######-###'),
            'pre_carriage' => 'By Sea/ By Air',
            'place_of_receipt' => 'HongKong / China',
            'country_of_origin' => 'China',
            'port_of_loading' => 'Any port of China/HongKong',
            'port_of_discharge' => 'CHATTOGRAM, BANGLADESH',
            'final_destination' => 'ICD KAMALAPUR, DHAKA, BANGLADESH',
            'delivery_payment_terms' => 'BY LCAF / TT',
            'incoterm' => 'CFR',
            'mark' => 'N/M',
            'declaration' => 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct',
        ];
    }

    /**
     * A bare invoice without shipping routing or advising bank details.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'advising_bank_name' => null,
            'advising_bank_address' => null,
            'advising_bank_swift' => null,
            'beneficiary_name' => null,
            'beneficiary_account' => null,
            'pre_carriage' => null,
            'place_of_receipt' => null,
            'country_of_origin' => null,
            'port_of_loading' => null,
            'port_of_discharge' => null,
            'final_destination' => null,
            'declaration' => null,
        ]);
    }
}
