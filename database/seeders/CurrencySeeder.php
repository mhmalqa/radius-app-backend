<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Currency;

use Illuminate\Support\Facades\Cache;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'الدولار الأمريكي',
                'symbol' => '$',
                'exchange_rate' => 1.00,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SYP',
                'name' => 'الليرة السورية',
                'symbol' => 'ل.س',
                'exchange_rate' => 15000.00,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'TRY',
                'name' => 'الليرة التركية',
                'symbol' => '₺',
                'exchange_rate' => 30.00,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }

        // Clear cache to ensure new values are reflected immediately
        Cache::forget('currencies_list');
    }
}
