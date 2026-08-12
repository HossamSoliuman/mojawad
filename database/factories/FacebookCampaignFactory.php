<?php

namespace Database\Factories;

use App\Models\FacebookCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FacebookCampaign>
 */
class FacebookCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => str($name)->title(),
            'slug' => str($name)->slug(),
            'goal' => fake()->sentence(),
            'audience' => fake()->sentence(),
            'cadence' => '3 posts per week',
            'tone' => fake()->sentence(),
            'core_hashtags' => '#mojawad #quran',
            'image_workflow' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
