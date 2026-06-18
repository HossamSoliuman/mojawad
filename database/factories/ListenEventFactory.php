<?php

namespace Database\Factories;

use App\Models\ListenEvent;
use App\Models\Qari;
use App\Models\Tilawa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListenEvent>
 */
class ListenEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qari = Qari::factory()->create();

        return [
            'user_id' => User::factory(),
            'qari_id' => $qari->id,
            'tilawa_id' => Tilawa::factory()->for($qari),
            'seconds' => fake()->numberBetween(1, 60),
            'listened_at' => now(),
        ];
    }
}
