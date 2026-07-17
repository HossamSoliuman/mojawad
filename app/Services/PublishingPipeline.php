<?php

namespace App\Services;

use App\Jobs\AlignSubtitles;
use App\Jobs\MasterTilawaAudio;
use App\Jobs\PublishToFacebook;
use App\Jobs\PublishToYoutube;
use App\Jobs\RenderBrandCover;
use App\Jobs\RenderBrandedVideo;
use App\Models\Publication;
use App\Models\Tilawa;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class PublishingPipeline
{
    public const PLATFORMS = ['youtube', 'facebook', 'podcast'];

    /**
     * Dispatch the brand chain. Subtitles + cover only need the ayah range, the
     * master embeds the cover, and the video needs the master + subtitles — so
     * the chain order encodes those dependencies.
     */
    public function prepare(Tilawa $tilawa): void
    {
        $tilawa->update(['brand_status' => 'processing', 'brand_error' => null]);

        Bus::chain([
            new AlignSubtitles($tilawa->id),
            new RenderBrandCover($tilawa->id),
            new MasterTilawaAudio($tilawa->id),
            new RenderBrandedVideo($tilawa->id),
        ])->dispatch();
    }

    /**
     * Create/refresh a Publication row per selected platform and dispatch the
     * matching publisher. Podcast has no upload API — "publishing" just completes
     * the row so the RSS feed picks it up on its next poll. The per-platform meta
     * (title, description) is stored on the row so the publishers ship exactly
     * what the admin composed in the Publishing tab.
     *
     * @param  list<string>  $platforms
     * @param  array<string, array{title?: string, description?: string}>  $meta
     */
    public function publish(Tilawa $tilawa, array $platforms, ?int $userId, array $meta = []): void
    {
        foreach (array_intersect($platforms, self::PLATFORMS) as $platform) {
            $publication = Publication::updateOrCreate(
                ['tilawa_id' => $tilawa->id, 'platform' => $platform],
                [
                    'status' => 'pending',
                    'error' => null,
                    'created_by' => $userId,
                    'meta' => $meta[$platform] ?? null,
                ],
            );

            match ($platform) {
                'youtube' => PublishToYoutube::dispatch($publication->id),
                'facebook' => PublishToFacebook::dispatch($publication->id),
                'podcast' => $this->completePodcast($tilawa, $publication),
            };
        }
    }

    private function completePodcast(Tilawa $tilawa, Publication $publication): void
    {
        $publication->update([
            'status' => 'completed',
            'external_url' => route('podcast.feed'),
            'published_at' => now(),
            'error' => null,
        ]);

        // The recitation is now public: surface it on the site and in the feed,
        // and move it into the final Published stage of the production pipeline.
        $tilawa->update([
            'status' => 'active',
            'review_status' => 'approved',
            'production_stage' => 'published',
        ]);

        Cache::forget('homepage_data');
    }
}
