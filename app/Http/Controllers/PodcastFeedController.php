<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PodcastFeedController extends Controller
{
    /**
     * A standard iTunes-namespaced RSS feed of the mastered recitations. Spotify
     * and Anghami cannot be pushed to; we submit this feed URL once and they poll
     * it, so "publishing" = a completed podcast Publication appearing here.
     */
    public function feed(): Response
    {
        $disk = Storage::disk(config('publishing.disk'));

        $items = Publication::with('tilawa.qari')
            ->where('platform', 'podcast')
            ->where('status', 'completed')
            ->latest('published_at')
            ->get()
            ->filter(fn (Publication $p) => $p->tilawa && $p->tilawa->master_audio_path)
            ->map(function (Publication $p) use ($disk) {
                $tilawa = $p->tilawa;

                return [
                    'guid' => 'tilawa-'.$tilawa->id,
                    'title' => $tilawa->title_ar,
                    'description' => $tilawa->qari?->name ?? '',
                    'audioUrl' => $tilawa->master_audio_url,
                    'audioBytes' => $disk->exists($tilawa->master_audio_path) ? $disk->size($tilawa->master_audio_path) : 0,
                    'coverUrl' => $tilawa->brand_cover_url,
                    'duration' => (int) $tilawa->duration,
                    'pubDate' => ($p->published_at ?? $p->updated_at)->toRssString(),
                ];
            })
            ->values();

        $xml = view('podcast.feed', [
            'channel' => config('publishing.podcast'),
            'items' => $items,
            'feedUrl' => route('podcast.feed'),
            'siteUrl' => config('app.url'),
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
