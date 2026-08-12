<?php

namespace Database\Factories;

use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacebookPost>
 */
class FacebookPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'facebook_campaign_id' => FacebookCampaign::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(array_keys(FacebookPost::CATEGORIES)),
            'status' => 'draft',
            'length' => fake()->randomElement(FacebookPost::LENGTHS),
            'hook' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'cta' => fake()->sentence(),
            'hashtags' => '#mojawad #quran',
            'visual_brief' => fake()->sentence(),
            'image_prompt' => fake()->paragraph(),
            'aspect_ratio' => fake()->randomElement(FacebookPost::ASPECT_RATIOS),
            'image_file' => fake()->slug().'.png',
            'alt_text' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (): array => ['status' => 'ready']);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => ['status' => 'scheduled', 'scheduled_for' => now()->addDay()]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => 'published', 'published_at' => now()]);
    }
}
