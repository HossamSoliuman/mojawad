<?php

namespace App\Services;

use Illuminate\Support\Arr;

/**
 * Reads the hand-written Facebook campaign file and normalises it into
 * ready-to-paste posts for the admin Facebook tab. The file is the single
 * source of truth: editors add or reword posts there, nothing is stored in
 * the database.
 *
 * @phpstan-type CampaignPost array{
 *     id: string,
 *     category: string,
 *     category_label: string,
 *     category_icon: string,
 *     category_color: string,
 *     length: string,
 *     visual: string,
 *     title: string,
 *     hook: string,
 *     body: list<string>,
 *     cta: string,
 *     hashtags: list<string>,
 *     visual_brief: string,
 *     visual_text: string,
 *     alt_text: string,
 *     verify: list<string>,
 *     publish_slot: string,
 *     text: string,
 *     char_count: int,
 *     image_prompt: string,
 *     aspect_ratio: string,
 *     image_file: string,
 *     image_path: string,
 *     image_ready: bool,
 *     image_version: int
 * }
 */
class SocialCampaign
{
    public const LENGTHS = ['short', 'long'];

    public const VISUALS = ['poster', 'still'];

    /** @var array<string, mixed>|null */
    private ?array $file = null;

    public function path(): string
    {
        return config('publishing.campaign_file');
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    /**
     * Folder the editor saves each generated image into.
     */
    public function imageDirectory(): string
    {
        return rtrim((string) config('publishing.campaign_images'), '\\/');
    }

    /**
     * @return CampaignPost|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->posts() as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return Arr::except($this->load()['campaign'] ?? [], ['_schema']);
    }

    /**
     * @return array<string, array{label: string, icon: string, color: string}>
     */
    public function categories(): array
    {
        $categories = [];

        foreach ($this->load()['categories'] ?? [] as $key => $category) {
            $categories[(string) $key] = [
                'label' => (string) ($category['label'] ?? $key),
                'icon' => (string) ($category['icon'] ?? 'fa-note-sticky'),
                'color' => (string) ($category['color'] ?? '#475569'),
            ];
        }

        return $categories;
    }

    /**
     * @return list<CampaignPost>
     */
    public function posts(): array
    {
        $categories = $this->categories();

        return array_values(array_map(
            fn (array $post): array => $this->normalise($post, $categories),
            array_filter($this->load()['posts'] ?? [], 'is_array'),
        ));
    }

    /**
     * @param  array<string, mixed>  $post
     * @param  array<string, array{label: string, icon: string, color: string}>  $categories
     * @return CampaignPost
     */
    private function normalise(array $post, array $categories): array
    {
        $category = (string) ($post['category'] ?? '');
        $meta = $categories[$category] ?? ['label' => $category, 'icon' => 'fa-note-sticky', 'color' => '#475569'];

        $hook = trim((string) ($post['hook'] ?? ''));
        $body = $this->stringList($post['body'] ?? []);
        $cta = trim((string) ($post['cta'] ?? ''));
        $hashtags = $this->stringList($post['hashtags'] ?? []);

        $text = implode("\n\n", array_filter([
            $hook,
            implode("\n\n", $body),
            $cta,
            implode(' ', $hashtags),
        ], fn (string $block): bool => $block !== ''));

        // basename() keeps a hand-edited campaign file from pointing the
        // preview and download routes outside the images folder.
        $imageFile = basename(trim((string) ($post['image_file'] ?? '')));
        $imagePath = $imageFile !== '' ? $this->imageDirectory().DIRECTORY_SEPARATOR.$imageFile : '';
        $imageReady = $imagePath !== '' && is_file($imagePath);

        return [
            'id' => (string) ($post['id'] ?? ''),
            'category' => $category,
            'category_label' => $meta['label'],
            'category_icon' => $meta['icon'],
            'category_color' => $meta['color'],
            'length' => in_array($post['length'] ?? null, self::LENGTHS, true) ? (string) $post['length'] : 'short',
            'visual' => in_array($post['visual'] ?? null, self::VISUALS, true) ? (string) $post['visual'] : 'poster',
            'title' => trim((string) ($post['title'] ?? '')),
            'hook' => $hook,
            'body' => $body,
            'cta' => $cta,
            'hashtags' => $hashtags,
            'visual_brief' => trim((string) ($post['visual_brief'] ?? '')),
            'visual_text' => trim((string) ($post['visual_text'] ?? '')),
            'alt_text' => trim((string) ($post['alt_text'] ?? '')),
            'verify' => $this->stringList($post['verify'] ?? []),
            'publish_slot' => trim((string) ($post['publish_slot'] ?? '')),
            'text' => $text,
            'char_count' => mb_strlen($text),
            'image_prompt' => implode("\n\n", $this->stringList($post['image_prompt'] ?? [])),
            'aspect_ratio' => trim((string) ($post['aspect_ratio'] ?? '')),
            'image_file' => $imageFile,
            'image_path' => $imagePath,
            'image_ready' => $imageReady,
            'image_version' => $imageReady ? (int) filemtime($imagePath) : 0,
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            $value = [$value];
        }

        return array_values(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $value),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * A malformed or missing file must not break the admin page — it renders an
     * empty state instead, pointing the editor at the file that needs fixing.
     *
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if ($this->file !== null) {
            return $this->file;
        }

        if (! $this->exists()) {
            return $this->file = [];
        }

        $decoded = json_decode((string) file_get_contents($this->path()), true);

        return $this->file = is_array($decoded) ? $decoded : [];
    }
}
