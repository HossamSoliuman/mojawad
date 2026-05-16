<?php

namespace App\Jobs;

use App\Models\Tilawa;
use App\Services\ArchiveOrgService;
use App\Services\AudioDurationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadToArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 900;

    public function __construct(
        public Tilawa $tilawa,
        public string $tempPath,
        public string $archiveFilename,
        public array  $metadata = []
    ) {}

    public function handle(ArchiveOrgService $archive): void
    {
        $absolutePath = Storage::disk('local')->path($this->tempPath);

        if (!file_exists($absolutePath)) {
            $this->tilawa->updateQuietly([
                'upload_status' => 'failed',
                'upload_error'  => 'Temp file not found: ' . $this->tempPath,
            ]);
            Log::error("[UploadToArchiveJob] Temp file missing for Tilawa #{$this->tilawa->id}");
            return;
        }

        $this->tilawa->updateQuietly(['upload_status' => 'uploading']);

        try {
            $result = $archive->upload(
                file:     $absolutePath,
                itemId:   config('archive.default_item_id'),
                filename: $this->archiveFilename,
                metadata: $this->metadata
            );

            $duration = AudioDurationService::getSeconds($result['url']);

            $this->tilawa->updateQuietly([
                'archive_url'         => $result['url'],
                'archive_item_id'     => $result['item_id'],
                'archive_filename'    => $result['filename'],
                'migrated_to_archive' => true,
                'upload_status'       => 'done',
                'upload_error'        => null,
                'duration'            => $duration,
            ]);

            Log::info("[UploadToArchiveJob] Tilawa #{$this->tilawa->id} uploaded OK → {$result['url']}");
        } catch (\Throwable $e) {
            $this->tilawa->updateQuietly([
                'upload_status' => 'failed',
                'upload_error'  => $e->getMessage(),
            ]);
            Log::error("[UploadToArchiveJob] Failed for Tilawa #{$this->tilawa->id}: " . $e->getMessage());
            throw $e;
        } finally {
            Storage::disk('local')->delete($this->tempPath);
        }
    }
}
