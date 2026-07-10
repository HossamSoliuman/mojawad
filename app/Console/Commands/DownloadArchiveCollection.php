<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DownloadArchiveCollection extends Command
{
    protected $signature = 'quran:download {identifier=way2sona_20160405_2210}';

    protected $description = 'Downloads audio items from Archive.org';

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

        $this->info('Found '.count($audioFiles).' tracks. Starting download loop...');

        foreach ($audioFiles as $fileInfo) {
            $originalFilename = $fileInfo['name'] ?? null;
            $displayTitle = $fileInfo['title'] ?? null;

            if (! $originalFilename) {
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

            $directoryPath = storage_path("recitations/{$identifier}");
            $fullPath = "{$directoryPath}/{$cleanTitle}";
            $downloadUrl = "https://archive.org/download/{$identifier}/{$originalFilename}";

            if (file_exists($fullPath)) {
                $this->line("Skipping (Already exists): {$cleanTitle}");

                continue;
            }

            $this->info("Downloading: {$cleanTitle}");

            try {
                if (! file_exists($directoryPath)) {
                    mkdir($directoryPath, 0755, true);
                }

                $downloadResponse = Http::timeout(0)->sink($fullPath)->get($downloadUrl);

                if (! $downloadResponse->successful()) {
                    $this->error(" -> Failed to download asset: {$originalFilename}");
                }
            } catch (\Exception $e) {
                $this->error(" -> Error handling file {$originalFilename}: ".$e->getMessage());
            }
        }

        $this->info("All items from collection '{$identifier}' have been processed!");

        return Command::SUCCESS;
    }
}
