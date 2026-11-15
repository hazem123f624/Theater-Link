<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Models\Settings;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            'SA' => [
                'name_en' => 'Saudi Arabia',
                'name_ar' => 'المملكة العربية السعودية',
                'phone_code' => '+966',
                'phone_length' => 9,
                'currency' => 'SAR',
                'currency_symbol' => 'ر.س',
                'cities' => [
                    ['Riyadh', 'الرياض'],
                    ['Jeddah', 'جدة'],
                    ['Dammam', 'الدمام'],
                    ['Mecca', 'مكة المكرمة'],
                    ['Medina', 'المدينة المنورة'],
                ],
            ],
            'EG' => [
                'name_en' => 'Egypt',
                'name_ar' => 'مصر',
                'phone_code' => '+20',
                'phone_length' => 10,
                'currency' => 'EGP',
                'currency_symbol' => 'ج.م',
                'cities' => [
                    ['Cairo', 'القاهرة'],
                    ['Alexandria', 'الإسكندرية'],
                    ['Giza', 'الجيزة'],
                ],
            ],
        ];

        foreach ($countries as $code => $country) {
            $countryModel = Settings::updateOrCreate(
                [
                    'type' => SettingsType::COUNTRY->value,
                    'key' => $code,
                ],
                [
                    'name_en' => $country['name_en'],
                    'name_ar' => $country['name_ar'],
                    'data' => [
                        'code' => $code,
                        'phone_code' => $country['phone_code'],
                        'phone_length' => $country['phone_length'],
                        'currency' => $country['currency'],
                        'currency_symbol' => $country['currency_symbol'],
                    ],
                    'is_active' => true,
                ]
            );

            foreach ($country['cities'] as [$nameEn, $nameAr]) {
                Settings::updateOrCreate(
                    [
                        'type' => SettingsType::CITY->value,
                        'setting_id' => $countryModel->id,
                        'name_en' => $nameEn,
                    ],
                    [
                        'name_ar' => $nameAr,
                        'data' => ['country_code' => $code],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
