<?php

namespace Database\Factories;

use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tilawa>
 */
class TilawaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'qari_id' => Qari::factory(),
            'title_ar' => $title,
            'title_en' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'description_ar' => fake()->optional()->paragraph(),
            'description_en' => fake()->optional()->paragraph(),
            'recorded_at' => fake()->optional()->date(),
            'recorded_place' => fake()->optional()->city(),
            'audio_path' => 'tilawat/'.Str::random(12).'.mp3',
            'duration' => fake()->numberBetween(120, 3600),
            'cover_image' => null,
            'uploaded_by' => User::factory(),
            'is_featured' => false,
            'status' => 'pending',
            'review_status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'review_status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(?string $note = null): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
            'review_status' => 'rejected',
            'rejection_note' => $note ?? fake()->sentence(),
            'reviewed_at' => now(),
        ]);
    }
}
