<?php

namespace App\Services;

use App\Exceptions\FacebookPublishException;
use App\Models\FacebookPost;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Publishes campaign copy to the Facebook Page feed. Its sibling
 * {@see FacebookPublisher} handles branded videos for the production pipeline;
 * both read the same page credentials from `config('publishing.facebook')`.
 */
class FacebookPostPublisher
{
    /** Seconds allowed for the identity probe, which uploads nothing. */
    private const PROBE_TIMEOUT = 30;

    public static function enabled(): bool
    {
        return filled(config('publishing.facebook.page_id'))
            && filled(config('publishing.facebook.access_token'));
    }

    /**
     * @return array{id: string, url: string}
     */
    public function publish(FacebookPost $post): array
    {
        $message = $post->fullText();

        if (trim($message) === '') {
            throw new RuntimeException('The post has no copy to publish.');
        }

        return $this->read($post->hasImage()
            ? $this->postPhoto($post, $message)
            : $this->postMessage($message));
    }

    /**
     * Posts bare copy with no campaign record behind it, so a diagnostic can
     * prove the page credentials end to end over the same wire the scheduler uses.
     *
     * @return array{id: string, url: string}
     */
    public function publishMessage(string $message): array
    {
        if (trim($message) === '') {
            throw new RuntimeException('The post has no copy to publish.');
        }

        return $this->read($this->postMessage($message));
    }

    /**
     * Asks the Graph API who the token belongs to, answering with the rejection
     * when it is no longer usable. The scheduler probes this before claiming
     * anything: a dead token would otherwise be spent one post at a time, each
     * attempt consuming a post that then needs re-arming by hand.
     */
    public function credentialProblem(): ?string
    {
        $version = config('publishing.facebook.graph_version');

        $response = Http::timeout(self::PROBE_TIMEOUT)->get("https://graph.facebook.com/{$version}/me", [
            'fields' => 'id',
            'access_token' => $this->token(),
        ]);

        return $response->failed() ? $this->readError($response) : null;
    }

    /**
     * @return array{id: string, url: string}
     */
    private function read(Response $response): array
    {
        if ($response->failed()) {
            throw FacebookPublishException::rejected($response);
        }

        /**
         * A photo upload returns the image id plus the `post_id` of the story
         * it created; a plain feed post only returns the story id.
         */
        $id = (string) ($response->json('post_id') ?? $response->json('id') ?? '');

        if ($id === '') {
            throw new RuntimeException('Facebook did not return a post id.');
        }

        return ['id' => $id, 'url' => "https://www.facebook.com/{$id}"];
    }

    /** Removes a post from the page, letting a diagnostic clean up after itself. */
    public function deletePost(string $id): void
    {
        $version = config('publishing.facebook.graph_version');

        $response = $this->request()->delete("https://graph.facebook.com/{$version}/{$id}", [
            'access_token' => $this->token(),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Facebook refused to delete the post: '.$this->readError($response));
        }
    }

    private function postPhoto(FacebookPost $post, string $message): Response
    {
        $image = $post->imagePath();

        return $this->request()
            ->attach('source', (string) file_get_contents($image), basename($image))
            ->post($this->endpoint('photos'), [
                'access_token' => $this->token(),
                'message' => $message,
                'published' => 'true',
            ]);
    }

    private function postMessage(string $message): Response
    {
        return $this->request()->asForm()->post($this->endpoint('feed'), [
            'access_token' => $this->token(),
            'message' => $message,
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::timeout((int) config('publishing.render_timeout'));
    }

    private function endpoint(string $edge): string
    {
        $version = config('publishing.facebook.graph_version');
        $pageId = config('publishing.facebook.page_id');

        return "https://graph.facebook.com/{$version}/{$pageId}/{$edge}";
    }

    private function token(): string
    {
        return (string) config('publishing.facebook.access_token');
    }

    private function readError(Response $response): string
    {
        return FacebookPublishException::readError($response);
    }
}
