<?php

namespace Database\Factories;

use App\Models\Qari;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Qari>
 */
class QariFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name_ar' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'image' => null,
            'biography_ar' => fake()->optional()->sentence(),
            'biography_en' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'is_featured' => false,
            'status' => 'active',
        ];
    }
}
