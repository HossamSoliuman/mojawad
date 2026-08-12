<?php

namespace Database\Seeders;

use App\Models\FacebookCampaign;
use App\Models\FacebookPost;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacebookCampaignSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FacebookCampaign::factory()
            ->has(FacebookPost::factory()->count(5), 'posts')
            ->create([
                'name' => 'Facebook content campaign',
                'slug' => 'facebook-content-campaign',
            ]);
    }
}
