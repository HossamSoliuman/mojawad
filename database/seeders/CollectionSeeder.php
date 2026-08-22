<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Tilawa;
use App\Services\AutoCollectionResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAutoCollections();
        $this->seedManualCollections();
    }

    /**
     * The stat-driven playlists that keep themselves up to date. Admins can
     * rename, reorder, hide, or delete any of them from /admin/collections.
     */
    private function seedAutoCollections(): void
    {
        $sets = [
            [
                'slug' => 'rare-gems',
                'title_ar' => 'تلاوات نادرة لكبار القراء',
                'title_en' => 'Rare Gems of the Great Qaris',
                'description_ar' => 'كنوز مخفية: أقل التلاوات استماعًا من أشهر القراء، تستحق أن تُسمع.',
                'auto_rule' => AutoCollectionResolver::RULE_RARE_GEMS,
                'auto_limit' => 12,
            ],
            [
                'slug' => 'most-played',
                'title_ar' => 'الأكثر استماعًا',
                'title_en' => 'Most Played',
                'description_ar' => 'أكثر عشر تلاوات استماعًا على المنصة.',
                'auto_rule' => AutoCollectionResolver::RULE_MOST_PLAYED,
                'auto_limit' => 10,
            ],
            [
                'slug' => 'most-liked',
                'title_ar' => 'الأكثر إعجابًا',
                'title_en' => 'Most Liked',
                'description_ar' => 'أكثر عشر تلاوات نالت إعجاب المستمعين.',
                'auto_rule' => AutoCollectionResolver::RULE_MOST_LIKED,
                'auto_limit' => 10,
            ],
            [
                'slug' => 'trending-this-week',
                'title_ar' => 'الأكثر رواجًا هذا الأسبوع',
                'title_en' => 'Trending This Week',
                'description_ar' => 'ما يستمع إليه الناس الآن خلال الأيام السبعة الماضية.',
                'auto_rule' => AutoCollectionResolver::RULE_TRENDING,
                'auto_limit' => 10,
            ],
            [
                'slug' => 'newly-added',
                'title_ar' => 'أحدث التلاوات',
                'title_en' => 'Newly Added',
                'description_ar' => 'آخر ما أضيف إلى المكتبة من تلاوات.',
                'auto_rule' => AutoCollectionResolver::RULE_RECENT,
                'auto_limit' => 12,
            ],
        ];

        foreach ($sets as $i => $set) {
            Collection::firstOrCreate(
                ['slug' => $set['slug']],
                [...$set, 'type' => Collection::TYPE_AUTO, 'is_active' => true, 'sort_order' => $i],
            );
        }
    }

    private function seedManualCollections(): void
    {
        $sets = [
            ['title_ar' => 'مختارات رمضان', 'title_en' => 'Ramadan Favorites', 'description_ar' => 'تلاوات مختارة لشهر رمضان المبارك.'],
            ['title_ar' => 'تلاوات خاشعة', 'title_en' => 'Soothing Recitations', 'description_ar' => 'أجمل التلاوات الهادئة للاستماع والتدبر.'],
        ];

        foreach ($sets as $i => $set) {
            $collection = Collection::firstOrCreate(
                ['slug' => Str::slug($set['title_en'])],
                [...$set, 'type' => Collection::TYPE_MANUAL, 'is_active' => true, 'sort_order' => 10 + $i],
            );

            $tilawat = Tilawa::where('status', 'active')->inRandomOrder()->take(8)->pluck('id');

            $collection->tilawat()->syncWithoutDetaching(
                $tilawat->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])->all()
            );
        }
    }
}
