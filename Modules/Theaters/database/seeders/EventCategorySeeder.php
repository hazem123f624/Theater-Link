<?php

namespace Modules\Theaters\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Theaters\Models\EventCategory;
use Modules\Theaters\Models\EventCategoryTranslation;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'theater', 'en' => 'Theater', 'ar' => 'مسرح'],
            ['slug' => 'music', 'en' => 'Music', 'ar' => 'موسيقى'],
            ['slug' => 'comedy', 'en' => 'Comedy', 'ar' => 'كوميديا'],
            ['slug' => 'dance', 'en' => 'Dance', 'ar' => 'رقص'],
            ['slug' => 'family', 'en' => 'Family', 'ar' => 'عائلي'],
            ['slug' => 'workshop', 'en' => 'Workshop', 'ar' => 'ورشة'],
        ];

        foreach ($categories as $categoryData) {
            $category = EventCategory::query()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                ['is_active' => true]
            );

            foreach (['en', 'ar'] as $lang) {
                EventCategoryTranslation::query()->updateOrCreate(
                    ['category_id' => $category->id, 'lang' => $lang],
                    ['name' => $categoryData[$lang]]
                );
            }
        }
    }
}
