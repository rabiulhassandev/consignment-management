<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

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

        $settings = [
            'company_name' => 'Guangzhou Bnoor Global Trading Company Limited',
            'company_tagline' => 'Global Sourcing & Freight Forwarding',
            'china_office_address' => '广东省广州市黄埔区 - 九龙大道绿地·云创园，海丝知识中心T5栋1611房',
            'china_office_contact' => 'Mohammad Hozzatullah · +86 132 4735 7571',
            'dhaka_office_address' => 'House 14, Road 12, Sector 13, Uttara, Dhaka 1230, Bangladesh',
            'dhaka_office_contact' => 'Mahbub · +880 1622-143726',
            'bank_name' => 'The City Bank Limited',
            'bank_account_name' => 'Amatullah Trade International',
            'bank_account_number' => '1504311841001',
            'bank_branch' => 'Sonargaon Janapath Branch, Uttara, Dhaka 1230',
            'invoice_footer_note' => 'The Barakah of Noor — may Allah give us Barakah in our business, Ameen.',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings.all');
    }
}
