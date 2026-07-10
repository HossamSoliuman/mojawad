<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MultiSelectQuranDownload extends Command
{
    protected $signature = 'quran:select';

    protected $description = 'Search, multi-select, and download collections from a specific creator into a single folder';

    public function handle()
    {
        $creator = 'الشيخ محمود خليل الحصري';
        $searchUrl = 'https://archive.org/advancedsearch.php?q='.urlencode('creator:"'.$creator.'" AND mediatype:(audio)').'&fl[]=identifier&fl[]=title&sort[]=downloads+desc&rows=40&output=json';

        $searchResponse = Http::timeout(0)->get($searchUrl);

        if (! $searchResponse->successful()) {
            $this->error('Archive.org search API failed.');

            return Command::FAILURE;
        }

        $docs = $searchResponse->json('response.docs', []);

        if (empty($docs)) {
            $this->error('No active collections found for this creator.');

            return Command::FAILURE;
        }

        $options = [];
        foreach ($docs as $doc) {
            if (! empty($doc['identifier']) && ! empty($doc['title'])) {
                $options[] = $doc['title'].' [ID: '.$doc['identifier'].']';
            }
        }

        $selectedItems = $this->choice(
            'Select which collections to download (Provide comma-separated numbers, e.g., 0,2,3):',
            $options,
            null,
            null,
            true
        );

        // Unified directory path for all downloads
        $directoryPath = storage_path('recitations/all_collections');

        if (! file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        foreach ($selectedItems as $selectedText) {
            preg_match('/\[ID:\s*(.*?)\]$/', $selectedText, $matches);
            if (empty($matches[1])) {
                continue;
            }

            $identifier = $matches[1];
            $this->info("Processing collection: {$identifier}");

            $metadataUrl = "https://archive.org/metadata/{$identifier}";
            $metadataResponse = Http::timeout(0)->get($metadataUrl);

            if (! $metadataResponse->successful()) {
                $this->error("Failed to fetch metadata for: {$identifier}");

                continue;
            }

            $files = $metadataResponse->json('files', []);
            $audioFiles = array_filter($files, function ($file) {
                $format = $file['format'] ?? '';

                return $format === 'VBR MP3' || $format === 'MP3';
            });

            if (empty($audioFiles)) {
                $this->warn("Collection {$identifier} contains no MP3 files.");

                continue;
            }

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

                // Prefixed with identifier to keep files unique within the flat folder
                $flatFilename = "{$identifier}-{$cleanTitle}";
                $fullPath = "{$directoryPath}/{$flatFilename}";
                $downloadUrl = "https://archive.org/download/{$identifier}/{$originalFilename}";

                if (file_exists($fullPath)) {
                    $this->line("Skipping (Already exists): {$flatFilename}");

                    continue;
                }

                $this->info("Downloading: {$flatFilename}");

                try {
                    $downloadResponse = Http::timeout(0)->sink($fullPath)->get($downloadUrl);

                    if (! $downloadResponse->successful()) {
                        $this->error(" -> Failed to download: {$originalFilename}");
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                } catch (\Exception $e) {
                    $this->error(" -> Error handling file {$originalFilename}: ".$e->getMessage());
                }
            }
        }

        $this->info('All selected collections processed successfully into a single directory!');

        return Command::SUCCESS;
    }
}
