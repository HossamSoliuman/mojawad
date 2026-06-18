<?php

namespace Database\Factories;

use App\Models\Qari;
use App\Models\TilawatSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TilawatSource>
 */
class TilawatSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $videoId = fake()->regexify('[A-Za-z0-9_-]{11}');

        return [
            'tilawa_id' => null,
            'source_type' => 'youtube',
            'source_url' => 'https://www.youtube.com/watch?v='.$videoId,
            'source_video_id' => $videoId,
            'source_title' => fake()->sentence(4),
            'thumbnail_url' => 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg',
            'qari_id' => Qari::factory(),
            'status' => 'pending',
            'error' => null,
            'created_by' => User::factory(),
            'processed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => 'processing']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'processed_at' => now()]);
    }

    public function failed(?string $error = null): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error' => $error ?? fake()->sentence(),
            'processed_at' => now(),
        ]);
    }
}
