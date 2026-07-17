<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FacebookPublisher
{
    public static function enabled(): bool
    {
        return filled(config('publishing.facebook.page_id'))
            && filled(config('publishing.facebook.access_token'));
    }

    /**
     * @return array{id: string, url: string}
     */
    public function publish(Publication $publication): array
    {
        $tilawa = $publication->tilawa;

        if ($tilawa === null || ! $tilawa->brand_video_path) {
            throw new RuntimeException('No branded video to publish.');
        }

        $videoAbs = Storage::disk(config('publishing.disk'))->path($tilawa->brand_video_path);

        if (! is_file($videoAbs)) {
            throw new RuntimeException("Branded video missing: {$tilawa->brand_video_path}");
        }

        $version = config('publishing.facebook.graph_version');
        $pageId = config('publishing.facebook.page_id');
        $endpoint = "https://graph-video.facebook.com/{$version}/{$pageId}/videos";

        $meta = $publication->meta ?? [];

        $response = Http::timeout((int) config('publishing.render_timeout'))
            ->attach('source', file_get_contents($videoAbs) ?: '', basename($videoAbs))
            ->post($endpoint, [
                'access_token' => config('publishing.facebook.access_token'),
                'title' => filled($meta['title'] ?? null) ? $meta['title'] : $tilawa->title_ar,
                'description' => filled($meta['description'] ?? null) ? $meta['description'] : $this->description($tilawa),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Facebook upload failed: '.$response->body());
        }

        $id = (string) $response->json('id');

        if ($id === '') {
            throw new RuntimeException('Facebook upload did not return a video id.');
        }

        return ['id' => $id, 'url' => "https://www.facebook.com/{$id}"];
    }

    private function description($tilawa): string
    {
        $link = 'https://mojawad.org?utm_source=facebook&utm_medium=video&utm_campaign=publishing';

        return trim(($tilawa->qari?->name ? $tilawa->qari->name."\n" : '').$link);
    }
}
