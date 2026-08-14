<?php

namespace App\Jobs;

use App\Exceptions\FacebookPublishException;
use App\Models\FacebookPost;
use App\Services\FacebookPostPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class PublishFacebookPost implements ShouldQueue
{
    use Queueable;

    /**
     * Never retried: a timeout can still leave a live post on the Page, so a
     * second attempt risks publishing the same copy twice. Failures surface in
     * the workspace for a human to review and publish again.
     */
    public int $tries = 1;

    public function __construct(public int $postId) {}

    public function handle(FacebookPostPublisher $publisher): void
    {
        $post = FacebookPost::query()->find($this->postId);

        if ($post === null || $post->isLive()) {
            return;
        }

        if (! FacebookPostPublisher::enabled()) {
            $post->update(['publish_error' => __('Facebook is not configured.')]);

            return;
        }

        $post->update(['publish_attempted_at' => now(), 'publish_error' => null]);

        $result = $publisher->publish($post);

        $post->update([
            'status' => 'published',
            'published_at' => now(),
            'external_id' => $result['id'],
            'external_url' => $result['url'],
            'publish_error' => null,
        ]);
    }

    /**
     * A refused token never reached the page, so the post keeps its slot and
     * publishes itself once the credentials are replaced. Every other failure
     * stays claimed, because the attempt may have left a live post behind.
     */
    public function failed(Throwable $exception): void
    {
        $post = FacebookPost::query()->find($this->postId);

        if ($post === null) {
            return;
        }

        $post->update([
            'publish_error' => Str::limit($exception->getMessage(), 1000),
            'publish_attempted_at' => $exception instanceof FacebookPublishException && $exception->credentialsRejected()
                ? null
                : $post->publish_attempted_at,
        ]);
    }
}
