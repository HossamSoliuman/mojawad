<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Services\FacebookPublisher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class PublishToFacebook implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $publicationId) {}

    public function handle(FacebookPublisher $publisher): void
    {
        $publication = Publication::with('tilawa.qari')->find($this->publicationId);

        if ($publication === null || $publication->status === 'completed') {
            return;
        }

        if (! FacebookPublisher::enabled()) {
            $publication->update(['status' => 'failed', 'error' => __('Facebook is not configured.')]);

            return;
        }

        $publication->update(['status' => 'processing', 'error' => null]);

        $result = $publisher->publish($publication);

        $publication->update([
            'status' => 'completed',
            'external_id' => $result['id'],
            'external_url' => $result['url'],
            'published_at' => now(),
            'error' => null,
        ]);

        $this->advanceToPublished($publication);
    }

    /**
     * A successful upload is the recitation's first live platform, so move it
     * out of the Publishing stage into Published.
     */
    private function advanceToPublished(Publication $publication): void
    {
        $tilawa = $publication->tilawa;

        if ($tilawa !== null && $tilawa->production_stage === 'publishing') {
            $tilawa->update(['production_stage' => 'published']);
        }
    }

    public function failed(Throwable $exception): void
    {
        Publication::find($this->publicationId)?->update([
            'status' => 'failed',
            'error' => Str::limit($exception->getMessage(), 1000),
        ]);
    }
}
