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
    /**
     * @return array<string, array{from: string, to: string}>
     */
    public function organize(Tilawa $tilawa): array
    {
        $changes = $this->plannedChanges($tilawa);
        $disk = $this->disk();

        foreach ($changes as $attribute => $change) {
            $disk->makeDirectory(Str::beforeLast($change['to'], '/'));

            if (! $disk->move($change['from'], $change['to'])) {
                throw new RuntimeException("Unable to move tilawa audio from {$change['from']} to {$change['to']}.");
            }

            $tilawa->updateQuietly([$attribute => $change['to']]);
        }

        return $changes;
    }

    /**
     * @return array<string, array{from: string, to: string}>
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

            if (! is_string($currentPath) || $currentPath === '' || ! $this->exists($currentPath)) {
                continue;
            }

            $destination = $this->destinationPath($tilawa, $directory, $currentPath);

            if (! $this->samePath($currentPath, $destination)) {
                $changes[$attribute] = ['from' => $currentPath, 'to' => $destination];
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

    /**
     * @return array<string, string>
     */
    private function managedPaths(): array
    {
        return [
            'audio_path' => (string) config('publishing.clean_dir'),
            'master_audio_path' => (string) config('publishing.master_dir'),
        ];
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

    private function extension(string $path): string
    {
        $extension = Str::lower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';

        return $extension !== '' ? $extension : 'mp3';
    }

    private function samePath(string $first, string $second): bool
    {
        return Str::lower(str_replace('\\', '/', $first)) === Str::lower(str_replace('\\', '/', $second));
    }
}
