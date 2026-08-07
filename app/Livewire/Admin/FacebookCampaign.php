<?php

namespace App\Livewire\Admin;

use App\Services\SocialCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class FacebookCampaign extends Component
{
    private ?SocialCampaign $campaign = null;

    #[Url]
    public string $category = 'all';

    #[Url]
    public string $length = 'all';

    #[Url]
    public string $visual = 'all';

    #[Url]
    public string $search = '';

    #[Url]
    public bool $needsImage = false;

    public function resetFilters(): void
    {
        $this->reset(['category', 'length', 'visual', 'search', 'needsImage']);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function meta(): array
    {
        return $this->campaign()->meta();
    }

    #[Computed]
    public function fileMissing(): bool
    {
        return ! $this->campaign()->exists();
    }

    #[Computed]
    public function filePath(): string
    {
        return $this->campaign()->path();
    }

    #[Computed]
    public function imageDirectory(): string
    {
        return $this->campaign()->imageDirectory();
    }

    /**
     * Open the images folder with the post's image selected, so the editor can
     * drag it straight into the Facebook composer.
     */
    public function revealImageInExplorer(string $postId): void
    {
        $post = $this->campaign()->find($postId);

        abort_unless($post !== null && $post['image_ready'], 404);

        $absolutePath = realpath($post['image_path']);

        abort_unless($absolutePath !== false, 404);

        if (PHP_OS_FAMILY === 'Windows') {
            Process::run(['cmd.exe', '/c', 'start', '', 'explorer.exe', '/select,'.$absolutePath]);

            return;
        }

        $command = PHP_OS_FAMILY === 'Darwin'
            ? ['open', '-R', $absolutePath]
            : ['xdg-open', dirname($absolutePath)];

        Process::run($command)->throw();
    }

    /**
     * Category filter chips, each carrying how many posts it holds.
     *
     * @return list<array{key: string, label: string, icon: string, color: string, count: int}>
     */
    #[Computed]
    public function categoryChips(): array
    {
        $posts = $this->campaign()->posts();

        $chips = [[
            'key' => 'all',
            'label' => __('All posts'),
            'icon' => 'fa-layer-group',
            'color' => '#334155',
            'count' => count($posts),
        ]];

        foreach ($this->campaign()->categories() as $key => $category) {
            $chips[] = [
                'key' => $key,
                'label' => $category['label'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'count' => count(array_filter($posts, fn (array $post): bool => $post['category'] === $key)),
            ];
        }

        return $chips;
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function posts(): array
    {
        $needle = trim($this->search);

        return array_values(array_filter(
            $this->campaign()->posts(),
            function (array $post) use ($needle): bool {
                if ($this->category !== 'all' && $post['category'] !== $this->category) {
                    return false;
                }

                if ($this->length !== 'all' && $post['length'] !== $this->length) {
                    return false;
                }

                if ($this->visual !== 'all' && $post['visual'] !== $this->visual) {
                    return false;
                }

                if ($this->needsImage && $post['image_ready']) {
                    return false;
                }

                return $needle === ''
                    || mb_stripos($post['title'].' '.$post['text'], $needle) !== false;
            },
        ));
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return $this->category !== 'all'
            || $this->length !== 'all'
            || $this->visual !== 'all'
            || $this->needsImage
            || trim($this->search) !== '';
    }

    public function render(): View
    {
        return view('livewire.admin.facebook-campaign');
    }

    /**
     * One instance per request keeps the campaign file read once, no matter how
     * many computed properties reach for it.
     */
    private function campaign(): SocialCampaign
    {
        return $this->campaign ??= app(SocialCampaign::class);
    }
}
