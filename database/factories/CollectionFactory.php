<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Services\AutoCollectionResolver;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title_ar' => $title,
            'title_en' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'type' => Collection::TYPE_MANUAL,
            'auto_rule' => null,
            'auto_limit' => 10,
            'description_ar' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function auto(string $rule = AutoCollectionResolver::RULE_MOST_PLAYED, int $limit = 10): static
    {
        return $this->state(fn () => [
            'type' => Collection::TYPE_AUTO,
            'auto_rule' => $rule,
            'auto_limit' => $limit,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
