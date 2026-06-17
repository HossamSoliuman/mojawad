<?php

namespace Database\Factories;

use App\Models\Short;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Short>
 */
class ShortFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['audio', 'video']);

        return [
            'title_ar' => fake()->sentence(3),
            'title_en' => fake()->sentence(3),
            'type' => $type,
            'media_path' => 'shorts/'.fake()->uuid().($type === 'video' ? '.mp4' : '.mp3'),
            'poster_path' => $type === 'audio' ? 'short-posters/'.fake()->uuid().'.jpg' : null,
            'created_by' => User::factory(),
            'sort_order' => fake()->numberBetween(0, 100),
            'status' => 'active',
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => ['type' => 'video', 'media_path' => 'shorts/'.fake()->uuid().'.mp4', 'poster_path' => null]);
    }

    public function audio(): static
    {
        return $this->state(fn () => ['type' => 'audio', 'media_path' => 'shorts/'.fake()->uuid().'.mp3', 'poster_path' => 'short-posters/'.fake()->uuid().'.jpg']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
