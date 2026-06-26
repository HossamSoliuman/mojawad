<?php

namespace Database\Factories;

use App\Models\Short;
use App\Models\ShortView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShortView>
 */
class ShortViewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'short_id' => Short::factory(),
            'viewer_key' => 'g:'.Str::uuid(),
            'views' => fake()->numberBetween(1, 20),
            'last_viewed_at' => now(),
        ];
    }
}
