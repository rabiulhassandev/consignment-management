<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@bnoorgroup.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
            ],
        );

        $admin->assignRole(RolesAndPermissionsSeeder::SUPER_ADMIN_ROLE);

        $currencies = [
            ['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$'],
            ['name' => 'Chinese Yuan', 'code' => 'CNY', 'symbol' => '¥'],
            ['name' => 'Bangladeshi Taka', 'code' => 'BDT', 'symbol' => '৳'],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->firstOrCreate(['code' => $currency['code']], $currency);
        }

        $categories = ['Cotton', 'Polyester', 'Linen', 'Silk', 'Denim'];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(['name' => $category]);
        }
    }
}
