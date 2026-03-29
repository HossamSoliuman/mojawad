<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class RecomputeScores extends Command
{
    protected $signature = 'videos:recompute-scores';
    protected $description = 'Recompute engagement scores for all approved videos';

    public function handle(): void
    {
        Video::approved()->chunk(100, function ($videos) {
            foreach ($videos as $video) {
                $video->recomputeScore();
            }
        });

        $this->info('Done.');
    }
}
