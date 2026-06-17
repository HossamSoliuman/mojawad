<?php

namespace Database\Factories;

use App\Models\Tilawa;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchHistory>
 */
class WatchHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $duration = fake()->numberBetween(120, 3600);

        return [
            'user_id' => User::factory(),
            'tilawa_id' => Tilawa::factory(),
            'position' => fake()->numberBetween(10, $duration - 30),
            'duration' => $duration,
            'completed' => false,
            'last_watched_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed' => true,
            'position' => $attributes['duration'] ?? 0,
        ]);
    }
}
