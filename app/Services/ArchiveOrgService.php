<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ArchiveOrgService
{
    protected string $accessKey;
    protected string $secretKey;
    protected string $baseUrl = 'https://s3.us.archive.org';

    public function __construct()
    {
        $this->accessKey = config('archive.access_key');
        $this->secretKey = config('archive.secret_key');
        set_time_limit(600);
    }

    public function upload(
        UploadedFile|string $file,
        string $itemId,
        string $filename,
        array $metadata = []
    ): array {
        $filePath = is_string($file) ? $file : $file->getRealPath();

        $headers = $this->buildHeaders($itemId, $filename, $metadata);

        $response = Http::withHeaders($headers)
            ->timeout(600)
            ->withBody(fopen($filePath, 'r'), 'audio/mpeg')
            ->put("{$this->baseUrl}/{$itemId}/{$filename}");

        if ($response->failed()) {
            throw new \RuntimeException(
                "Archive.org upload failed [{$response->status()}]: " . $response->body()
            );
        }

        return [
            'url'      => "https://archive.org/download/{$itemId}/{$filename}",
            'item_id'  => $itemId,
            'filename' => $filename,
        ];
    }

    public function delete(string $itemId, string $filename): bool
    {
        $response = Http::withHeaders([
            'Authorization'            => "LOW {$this->accessKey}:{$this->secretKey}",
            'x-archive-cascade-delete' => '1',
        ])->timeout(60)->delete("{$this->baseUrl}/{$itemId}/{$filename}");

        return $response->successful();
    }

    public function getUrl(string $itemId, string $filename): string
    {
        return "https://archive.org/download/{$itemId}/{$filename}";
    }

    public function getPublicUrl(string $itemId, string $filename): string
    {
        return $this->getUrl($itemId, $filename);
    }

    public function isQueueReady(string $itemId): bool
    {
        $response = Http::timeout(10)->get("{$this->baseUrl}/", [
            'check_limit' => 1,
            'accesskey'   => $this->accessKey,
            'bucket'      => $itemId,
        ]);

        return $response->successful() && ($response->json('over_limit') === 0);
    }

    protected function buildHeaders(string $itemId, string $filename, array $metadata): array
    {
        $headers = [
            'Authorization'              => "LOW {$this->accessKey}:{$this->secretKey}",
            'x-archive-auto-make-bucket' => '1',
            'x-archive-queue-derive'     => '0',
            'x-archive-meta-mediatype'   => 'audio',
            'x-archive-meta-collection'  => 'opensource_audio',
            'x-archive-meta-language'    => 'ara',
        ];

        $metaMap = [
            'title'       => 'x-archive-meta-title',
            'subject'     => 'x-archive-meta-subject',
            'description' => 'x-archive-meta-description',
            'creator'     => 'x-archive-meta-creator',
            'language'    => 'x-archive-meta-language',
            'mediatype'   => 'x-archive-meta-mediatype',
        ];

        foreach ($metaMap as $key => $header) {
            if (! empty($metadata[$key])) {
                $headers[$header] = (string) $metadata[$key];
            }
        }

        return $headers;
    }
}