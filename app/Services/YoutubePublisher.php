<?php

namespace App\Services;

use App\Models\Publication;
use Google\Client as GoogleClient;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class YoutubePublisher
{
    public static function enabled(): bool
    {
        return filled(config('publishing.youtube.client_id'))
            && filled(config('publishing.youtube.client_secret'))
            && filled(config('publishing.youtube.refresh_token'));
    }

    /**
     * Resumable upload of the branded video, then a best-effort caption track.
     *
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

        $client = $this->client();
        $service = new YouTube($client);

        $meta = $publication->meta ?? [];

        $snippet = new VideoSnippet;
        $snippet->setTitle(filled($meta['title'] ?? null) ? $meta['title'] : $tilawa->title_ar);
        $snippet->setDescription(filled($meta['description'] ?? null) ? $meta['description'] : $this->description($tilawa));
        $snippet->setCategoryId((string) config('publishing.youtube.category_id'));

        $status = new VideoStatus;
        $status->setPrivacyStatus((string) config('publishing.youtube.privacy_status'));
        $status->setMadeForKids((bool) config('publishing.youtube.made_for_kids'));

        $video = new Video;
        $video->setSnippet($snippet);
        $video->setStatus($status);

        $videoId = $this->resumableUpload($client, $service, $video, $videoAbs);

        $this->uploadCaption($client, $service, $videoId, $tilawa);

        return ['id' => $videoId, 'url' => 'https://youtu.be/'.$videoId];
    }

    private function client(): GoogleClient
    {
        $client = new GoogleClient;
        $client->setClientId((string) config('publishing.youtube.client_id'));
        $client->setClientSecret((string) config('publishing.youtube.client_secret'));
        $client->setScopes([YouTube::YOUTUBE_UPLOAD, YouTube::YOUTUBE_FORCE_SSL]);
        $client->setAccessType('offline');
        $client->fetchAccessTokenWithRefreshToken((string) config('publishing.youtube.refresh_token'));

        return $client;
    }

    private function resumableUpload(GoogleClient $client, YouTube $service, Video $video, string $videoAbs): string
    {
        $client->setDefer(true);

        $request = $service->videos->insert('snippet,status', $video);

        $media = new MediaFileUpload($client, $request, 'video/*', null, true, 1024 * 1024);
        $media->setFileSize(filesize($videoAbs) ?: 0);

        $uploaded = false;
        $handle = fopen($videoAbs, 'rb');

        try {
            while (! $uploaded && ! feof($handle)) {
                $uploaded = $media->nextChunk((string) fread($handle, 1024 * 1024));
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }

        if (! is_object($uploaded) || ! isset($uploaded['id'])) {
            throw new RuntimeException('YouTube upload did not return a video id.');
        }

        return (string) $uploaded['id'];
    }

    private function uploadCaption(GoogleClient $client, YouTube $service, string $videoId, $tilawa): void
    {
        if (! $tilawa->subtitle_path) {
            return;
        }

        $captionAbs = Storage::disk(config('publishing.disk'))->path($tilawa->subtitle_path);

        if (! is_file($captionAbs)) {
            return;
        }

        try {
            $snippet = new YouTube\CaptionSnippet;
            $snippet->setVideoId($videoId);
            $snippet->setLanguage('ar');
            $snippet->setName('العربية');

            $caption = new YouTube\Caption;
            $caption->setSnippet($snippet);

            $service->captions->insert('snippet', $caption, [
                'data' => (string) file_get_contents($captionAbs),
                'mimeType' => 'text/vtt',
                'uploadType' => 'multipart',
            ]);
        } catch (\Throwable $e) {
            // Captions are a best-effort enhancement; the burned-in copy still ships.
        }
    }

    private function description($tilawa): string
    {
        $link = 'https://mojawad.org?utm_source=youtube&utm_medium=video&utm_campaign=publishing';

        return trim(($tilawa->qari?->name ? $tilawa->qari->name."\n" : '').$link);
    }
}
