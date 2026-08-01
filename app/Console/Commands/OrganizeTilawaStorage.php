<?php

namespace App\Console\Commands;

use App\Models\Tilawa;
use App\Services\TilawaStorageService;
use Illuminate\Console\Command;

class OrganizeTilawaStorage extends Command
{
    protected $signature = 'tilawat:organize-storage {--dry-run : Show the planned file moves without changing storage}';

    protected $description = 'Name stored tilawa audio and video files and group them into qari folders';

    public function handle(TilawaStorageService $storage): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;

        Tilawa::query()
            ->with('qari')
            ->orderBy('id')
            ->chunkById(100, function ($tilawat) use ($storage, $dryRun, &$moved): void {
                foreach ($tilawat as $tilawa) {
                    $changes = $dryRun
                        ? $storage->plannedChanges($tilawa)
                        : $storage->organize($tilawa);

                    foreach ($changes as $change) {
                        $this->line("{$change['from']} -> {$change['to']}");
                        $moved++;
                    }
                }
            });

        $action = $dryRun ? 'planned' : 'organized';
        $this->info("{$moved} tilawa files {$action}.");

        return self::SUCCESS;
    }
}
