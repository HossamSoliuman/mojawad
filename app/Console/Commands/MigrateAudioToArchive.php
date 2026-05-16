<?php

namespace App\Console\Commands;

use App\Models\Tilawa;
use App\Services\ArchiveOrgService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateAudioToArchive extends Command
{
    protected $signature   = 'tilawat:migrate-to-archive {--dry-run : Preview only, no uploads}';
    protected $description = 'Upload all local MP3 files to Archive.org';

    public function handle(ArchiveOrgService $archive): int
    {
        $records = Tilawa::where('migrated_to_archive', false)->get();
        $this->info("Found {$records->count()} files to migrate.");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'Title', 'Local Path'], $records->map(fn($r) => [
                $r->id, $r->title, $r->audio_path
            ])->toArray());
            return 0;
        }

        $bar = $this->output->createProgressBar($records->count());

        foreach ($records as $tilawa) {
            try {
                $localPath = storage_path('app/public/' . $tilawa->audio_path);

                if (!file_exists($localPath)) {
                    $this->warn("Skipping #{$tilawa->id} — file not found: {$localPath}");
                    $bar->advance();
                    continue;
                }

                if (!$archive->isQueueReady(config('archive.default_item_id'))) {
                    $this->warn('Archive.org queue is busy — waiting 30 seconds...');
                    sleep(30);
                }

                $filename = basename($localPath);
                $result   = $archive->upload(
                    file: $localPath,
                    itemId: config('archive.default_item_id'),
                    filename: $filename,
                    metadata: [
                        'title'   => $tilawa->title,
                        'subject' => 'Quran;Tilawat;Islamic Audio',
                        'creator' => $tilawa->qari->name ?? '',
                    ]
                );

                $tilawa->update([
                    'archive_url'          => $result['url'],
                    'archive_item_id'      => $result['item_id'],
                    'archive_filename'     => $result['filename'],
                    'migrated_to_archive'  => true,
                ]);

                $bar->advance();
                sleep(1);

            } catch (\Throwable $e) {
                $this->error("Failed #{$tilawa->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Migration complete!');

        return 0;
    }
}
