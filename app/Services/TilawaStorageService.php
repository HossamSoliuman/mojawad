<?php

namespace App\Services;

use App\Models\Tilawa;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TilawaStorageService
{
    /** @var array<string, int> */
    private array $nextVideoSequences = [];

    /** @var array<string, int> */
    private array $reservedVideoSequences = [];

    /**
     * @return array<string, array{from: string, to: string, move: bool}>
     */
    public function organize(Tilawa $tilawa): array
    {
        $changes = $this->plannedChanges($tilawa);
        $disk = $this->disk();

        foreach ($changes as $attribute => $change) {
            if ($change['move']) {
                $disk->makeDirectory(Str::beforeLast($change['to'], '/'));

                if (! $disk->move($change['from'], $change['to'])) {
                    throw new RuntimeException("Unable to move tilawa file from {$change['from']} to {$change['to']}.");
                }
            }

            $tilawa->updateQuietly([$attribute => $change['to']]);
        }

        return $changes;
    }

    public function deleteFiles(Tilawa $tilawa): void
    {
        $paths = [
            $tilawa->audio_path,
            $tilawa->cover_image,
            $tilawa->subtitle_path,
            $tilawa->master_audio_path,
            $tilawa->brand_cover_path,
            $tilawa->brand_video_path,
            $tilawa->brand_card['card_image'] ?? null,
        ];

        foreach (array_unique(array_filter($paths, fn (mixed $path): bool => is_string($path) && $path !== '')) as $path) {
            $this->deletePath($path);
        }
    }

    public function deletePath(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        try {
            $this->disk()->delete($path);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @return array<string, array{from: string, to: string, move: bool}>
     */
    public function plannedChanges(Tilawa $tilawa): array
    {
        $tilawa->loadMissing('qari');

        if ($tilawa->qari === null) {
            return [];
        }

        $changes = [];

        foreach ($this->managedPaths() as $attribute => $directory) {
            $currentPath = $tilawa->getAttribute($attribute);

            if (! is_string($currentPath) || $currentPath === '') {
                continue;
            }

            if (! $this->exists($currentPath)) {
                $existingPath = $attribute === 'brand_video_path'
                    ? $this->existingOrganizedVideoPath($tilawa, $directory, $currentPath)
                    : null;

                if ($existingPath !== null && ! $this->samePath($currentPath, $existingPath)) {
                    $changes[$attribute] = ['from' => $currentPath, 'to' => $existingPath, 'move' => false];
                }

                continue;
            }

            $destination = $attribute === 'brand_video_path'
                ? $this->videoDestinationPath($tilawa, $directory, $currentPath)
                : $this->destinationPath($tilawa, $directory, $currentPath);

            if (! $this->samePath($currentPath, $destination)) {
                $changes[$attribute] = ['from' => $currentPath, 'to' => $destination, 'move' => true];
            }
        }

        return $changes;
    }

    public function destinationPath(Tilawa $tilawa, string $directory, string $currentPath): string
    {
        $tilawa->loadMissing('qari');

        $qariName = $this->sanitizeComponent($tilawa->qari?->name_ar ?: 'قارئ غير معروف', 50);
        $suffix = " للقاري الشيخ {$qariName}";
        $titleLimit = max(20, 140 - mb_strlen($suffix));
        $title = $this->sanitizeComponent($tilawa->title_ar ?: 'تلاوة', $titleLimit);
        $extension = $this->extension($currentPath);
        $baseName = $this->sanitizeComponent($title.$suffix, 140);
        $folder = trim($directory, '/').'/'.$qariName;
        $candidate = "{$folder}/{$baseName}.{$extension}";

        if ($this->samePath($candidate, $currentPath) || ! $this->exists($candidate)) {
            return $candidate;
        }

        $copy = 2;

        do {
            $candidate = "{$folder}/{$baseName} ({$copy}).{$extension}";
            $copy++;
        } while (! $this->samePath($candidate, $currentPath) && $this->exists($candidate));

        return $candidate;
    }

    public function videoDestinationPath(Tilawa $tilawa, string $directory, string $currentPath): string
    {
        $folder = $this->videoFolder($tilawa, $directory);
        $title = $this->videoTitle($tilawa);
        $extension = $this->extension($currentPath, 'mp4');
        $sequence = $this->videoSequence($folder, $currentPath);
        $candidate = $this->numberedVideoPath($folder, $sequence, $title, $extension);

        if ($this->samePath($candidate, $currentPath) || ! $this->exists($candidate)) {
            return $candidate;
        }

        $sequence = $this->reserveNextVideoSequence($folder, $currentPath);

        return $this->numberedVideoPath($folder, $sequence, $title, $extension);
    }

    private function existingOrganizedVideoPath(Tilawa $tilawa, string $directory, string $currentPath): ?string
    {
        $folder = $this->videoFolder($tilawa, $directory);
        $title = $this->videoTitle($tilawa);
        $extension = $this->extension($currentPath, 'mp4');
        $filePattern = '/^\d+\s*-\s*'.preg_quote($title, '/').'\.'.preg_quote($extension, '/').'$/u';
        $matches = array_values(array_filter(
            $this->disk()->files($folder),
            fn (string $path): bool => preg_match($filePattern, (string) pathinfo($path, PATHINFO_BASENAME)) === 1,
        ));

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function videoFolder(Tilawa $tilawa, string $directory): string
    {
        $tilawa->loadMissing('qari');
        $qariName = $this->sanitizeComponent($tilawa->qari?->name_ar ?: 'قارئ غير معروف', 50);

        return trim($directory, '/').'/'.$qariName;
    }

    private function videoTitle(Tilawa $tilawa): string
    {
        return $this->sanitizeComponent($tilawa->title_ar ?: 'تلاوة', 140);
    }

    /**
     * @return array<string, string>
     */
    private function managedPaths(): array
    {
        return [
            'audio_path' => (string) config('publishing.clean_dir'),
            'master_audio_path' => (string) config('publishing.master_dir'),
            'brand_video_path' => (string) config('publishing.video_dir'),
        ];
    }

    private function videoSequence(string $folder, string $currentPath): int
    {
        $currentFolder = str_replace('\\', '/', (string) pathinfo($currentPath, PATHINFO_DIRNAME));
        $currentName = (string) pathinfo($currentPath, PATHINFO_FILENAME);

        if ($this->samePath($currentFolder, $folder) && preg_match('/^(\d+)\s*-\s*/u', $currentName, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        return $this->reserveNextVideoSequence($folder, $currentPath);
    }

    private function reserveNextVideoSequence(string $folder, string $currentPath): int
    {
        $reservationKey = $folder.'|'.str_replace('\\', '/', $currentPath);

        if (isset($this->reservedVideoSequences[$reservationKey])) {
            return $this->reservedVideoSequences[$reservationKey];
        }

        if (! isset($this->nextVideoSequences[$folder])) {
            $highestSequence = 0;

            foreach ($this->disk()->files($folder) as $path) {
                $name = (string) pathinfo($path, PATHINFO_FILENAME);

                if (preg_match('/^(\d+)\s*-\s*/u', $name, $matches) === 1) {
                    $highestSequence = max($highestSequence, (int) $matches[1]);
                }
            }

            $this->nextVideoSequences[$folder] = $highestSequence + 1;
        }

        $sequence = $this->nextVideoSequences[$folder]++;
        $this->reservedVideoSequences[$reservationKey] = $sequence;

        return $sequence;
    }

    private function numberedVideoPath(string $folder, int $sequence, string $title, string $extension): string
    {
        return sprintf('%s/%03d - %s.%s', $folder, $sequence, $title, $extension);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('publishing.disk'));
    }

    private function exists(string $path): bool
    {
        try {
            return $this->disk()->exists($path);
        } catch (Throwable) {
            return false;
        }
    }

    private function sanitizeComponent(string $value, int $limit): string
    {
        $value = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F\x7F]/u', '', $value) ?? '';
        $value = trim(Str::squish($value), ". \t\n\r\0\x0B");
        $value = Str::limit($value, $limit, '');

        return $value !== '' ? $value : 'تلاوة';
    }

    private function extension(string $path, string $fallback = 'mp3'): string
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';

        return $extension !== '' ? $extension : $fallback;
    }

    private function samePath(string $first, string $second): bool
    {
        return Str::lower(str_replace('\\', '/', $first)) === Str::lower(str_replace('\\', '/', $second));
    }
}
