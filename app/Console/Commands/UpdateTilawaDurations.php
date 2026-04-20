<?php

namespace App\Console\Commands;

use App\Models\Tilawa;
use App\Services\AudioDurationService;
use Illuminate\Console\Command;

class UpdateTilawaDurations extends Command
{
    protected $signature = 'tilawa:update-durations
                            {--all : Re-process every record, not just missing ones}
                            {--id= : Process a single tilawa by ID}';

    protected $description = 'Backfill audio duration for uploaded tilawat';

    public function handle(): int
    {
        $query = Tilawa::query();

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        } elseif (! $this->option('all')) {
            $query->where(fn($q) => $q->whereNull('duration')->orWhere('duration', 0));
        }

        $tilawat = $query->get(['id', 'audio_path', 'duration', 'title']);

        if ($tilawat->isEmpty()) {
            $this->info('✔  Nothing to update.');
            return self::SUCCESS;
        }

        $this->info("Found {$tilawat->count()} tilawa(s) to process.");
        $bar = $this->output->createProgressBar($tilawat->count());
        $bar->start();

        $updated = 0;

        foreach ($tilawat as $tilawa) {
            $seconds = AudioDurationService::getSeconds($tilawa->audio_path);

            if ($seconds > 0) {
                $tilawa->updateQuietly(['duration' => $seconds]);
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✔  Updated {$updated} / {$tilawat->count()} record(s).");

        return self::SUCCESS;
    }
}
