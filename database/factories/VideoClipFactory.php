<?php

namespace Database\Factories;

use App\Models\Tilawa;
use App\Models\User;
use App\Models\VideoClip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VideoClip>
 */
class VideoClipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tilawa_id' => Tilawa::factory(),
            'created_by' => User::factory(),
            'template' => 'dark-waveform',
            'clip_start' => fake()->numberBetween(0, 120),
            'clip_duration' => fake()->randomElement([15, 30, 60]),
            'overlay_path' => 'clips/overlays/'.fake()->uuid().'.png',
            'caption_ar' => fake()->sentence(4),
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'output_path' => 'clips/'.fake()->uuid().'.mp4',
            'poster_path' => 'clips/posters/'.fake()->uuid().'.jpg',
            'duration' => fake()->randomElement([15, 30, 60]),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => fake()->sentence(),
        ]);
    }
}
