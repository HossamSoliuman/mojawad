<?php

namespace Database\Factories;

use App\Models\Publication;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tilawa_id' => Tilawa::factory(),
            'platform' => fake()->randomElement(['youtube', 'facebook', 'podcast']),
            'status' => 'pending',
            'created_by' => User::factory(),
        ];
    }

    public function podcast(): static
    {
        return $this->state(fn () => ['platform' => 'podcast']);
    }

    public function youtube(): static
    {
        return $this->state(fn () => ['platform' => 'youtube']);
    }

    public function facebook(): static
    {
        return $this->state(fn () => ['platform' => 'facebook']);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'external_id' => fake()->uuid(),
            'external_url' => fake()->url(),
            'published_at' => now(),
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
