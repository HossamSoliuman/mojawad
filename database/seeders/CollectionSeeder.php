<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Tilawa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            ['title_ar' => 'مختارات رمضان', 'title_en' => 'Ramadan Favorites', 'description_ar' => 'تلاوات مختارة لشهر رمضان المبارك.'],
            ['title_ar' => 'تلاوات خاشعة', 'title_en' => 'Soothing Recitations', 'description_ar' => 'أجمل التلاوات الهادئة للاستماع والتدبر.'],
        ];

        foreach ($sets as $i => $set) {
            $collection = Collection::firstOrCreate(
                ['slug' => Str::slug($set['title_en'])],
                [...$set, 'is_active' => true, 'sort_order' => $i],
            );

            $tilawat = Tilawa::where('status', 'active')->inRandomOrder()->take(8)->pluck('id');

            $collection->tilawat()->syncWithoutDetaching(
                $tilawat->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])->all()
            );
        }
    }
}
