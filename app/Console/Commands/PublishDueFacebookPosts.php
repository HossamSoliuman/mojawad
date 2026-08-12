<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebookPost;
use App\Models\FacebookPost;
use App\Services\FacebookPostPublisher;
use Illuminate\Console\Command;

class PublishDueFacebookPosts extends Command
{
    protected $signature = 'facebook:publish-due';

    protected $description = 'Publish scheduled Facebook campaign posts whose time has come';

    public function handle(): int
    {
        if (! FacebookPostPublisher::enabled()) {
            $this->warn('Facebook page credentials are missing; nothing was published.');

            return Command::SUCCESS;
        }

        $dispatched = 0;

        foreach (FacebookPost::query()->dueForPublishing()->pluck('id') as $postId) {
            if ($this->claim($postId)) {
                PublishFacebookPost::dispatch($postId);
                $dispatched++;
            }
        }

        $this->info("Queued {$dispatched} scheduled post(s) for publishing.");

        return Command::SUCCESS;
    }

    /**
     * Stamp the post as attempted in a single conditional update, so two
     * overlapping runs can never queue the same post twice.
     */
    private function claim(int $postId): bool
    {
        return FacebookPost::query()
            ->whereKey($postId)
            ->whereNull('publish_attempted_at')
            ->update(['publish_attempted_at' => now()]) === 1;
    }
}
