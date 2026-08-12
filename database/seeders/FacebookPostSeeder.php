<?php

namespace Database\Seeders;

use App\Models\FacebookPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacebookPostSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FacebookPost::factory()->count(5)->create();
    }
}
