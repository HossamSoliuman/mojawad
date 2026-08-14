<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebookPost;
use App\Models\FacebookPost;
use App\Services\FacebookPostPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublishDueFacebookPosts extends Command
{
    protected $signature = 'facebook:publish-due';

    protected $description = 'Publish scheduled Facebook campaign posts whose time has come';

    public function handle(FacebookPostPublisher $publisher): int
    {
        if (! FacebookPostPublisher::enabled()) {
            $this->warn('Facebook page credentials are missing; nothing was published.');

            return Command::SUCCESS;
        }

        $due = FacebookPost::query()->dueForPublishing()->pluck('id');

        if ($due->isEmpty()) {
            $this->info('Queued 0 scheduled post(s) for publishing.');

            return Command::SUCCESS;
        }

        $problem = $publisher->credentialProblem();

        if ($problem !== null) {
            return $this->holdQueue($due, $problem);
        }

        $dispatched = 0;

        foreach ($due as $postId) {
            if ($this->claim($postId)) {
                PublishFacebookPost::dispatch($postId);
                $dispatched++;
            }
        }

        $this->info("Queued {$dispatched} scheduled post(s) for publishing.");

        return Command::SUCCESS;
    }

    /**
     * Nothing is claimed while the page token is refused: the due posts keep
     * their slots and go out on the next run once a working token is in place,
     * with the rejection shown in the workspace meanwhile. Spending the token
     * post by post would instead retire the whole queue, one failed attempt
     * each, every one needing an editor to re-arm it by hand.
     *
     * @param  Collection<int, int>  $due
     */
    private function holdQueue(Collection $due, string $problem): int
    {
        FacebookPost::query()->whereKey($due)->update([
            'publish_error' => Str::limit(__('Facebook rejected the page credentials: :error', ['error' => $problem]), 1000),
        ]);

        $this->error("The page token was rejected, so {$due->count()} due post(s) are on hold: {$problem}");
        Log::warning('Facebook publishing is on hold: the page token was rejected.', ['error' => $problem]);

        return Command::FAILURE;
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
