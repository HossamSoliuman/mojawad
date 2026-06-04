<?php

namespace Database\Factories;

use App\Models\Tilawa;
use App\Models\TilawaReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TilawaReview>
 */
class TilawaReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tilawa_id' => Tilawa::factory(),
            'reviewer_id' => User::factory(),
            'action' => fake()->randomElement(['submitted', 'resubmitted', 'approved', 'rejected']),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
