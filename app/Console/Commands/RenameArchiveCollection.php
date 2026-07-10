<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RenameArchiveCollection extends Command
{
    protected $signature = 'quran:rename {identifier=Tasjilat-Mojawada_Kharijia_Mohamed-siddiq-al-minshawi_uP_bY_mUSLEm}';

    protected $description = 'Renames downloaded audio items from original filenames to Arabic titles';

    public function handle()
    {
        $identifier = $this->argument('identifier');
        $metadataUrl = "https://archive.org/metadata/{$identifier}";

        $this->info("Fetching metadata for collection: {$identifier}...");

        $response = Http::timeout(0)->get($metadataUrl);

        if (! $response->successful()) {
            $this->error('Failed to connect to Archive.org API.');

            return Command::FAILURE;
        }

        $files = $response->json('files', []);

        $audioFiles = array_filter($files, function ($file) {
            $format = $file['format'] ?? '';

            return $format === 'VBR MP3' || $format === 'MP3';
        });

        if (empty($audioFiles)) {
            $this->warn('No MP3 files found in this collection.');

            return Command::SUCCESS;
        }

        $directoryPath = storage_path("recitations/{$identifier}");

        foreach ($audioFiles as $fileInfo) {
            $originalFilename = $fileInfo['name'] ?? null;
            $displayTitle = $fileInfo['title'] ?? null;

            if (! $originalFilename) {
                continue;
            }

            $oldFullPath = "{$directoryPath}/{$originalFilename}";

            if (! file_exists($oldFullPath)) {
                continue;
            }

            if (! $displayTitle) {
                $displayTitle = $originalFilename;
            }

            $cleanTitle = preg_replace('/[\/\\\\\:\*\?"<>\|]/', '', $displayTitle);
            $cleanTitle = trim($cleanTitle);

            if (! Str::endsWith(strtolower($cleanTitle), '.mp3')) {
                $cleanTitle .= '.mp3';
            }

            $newFullPath = "{$directoryPath}/{$cleanTitle}";

            if (file_exists($newFullPath) && $oldFullPath !== $newFullPath) {
                $this->line("Target title already exists, removing duplicate original file: {$originalFilename}");
                unlink($oldFullPath);

                continue;
            }

            $this->info("Renaming: {$originalFilename} -> {$cleanTitle}");
            rename($oldFullPath, $newFullPath);
        }

        $this->info("Renaming completed for collection '{$identifier}'!");

        return Command::SUCCESS;
    }
}
