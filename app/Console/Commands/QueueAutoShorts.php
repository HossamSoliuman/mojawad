<?php

namespace App\Console\Commands;

use App\Models\Qari;
use App\Models\User;
use App\Services\AutoShortQueue;
use Illuminate\Console\Command;

class QueueAutoShorts extends Command
{
    protected $signature = 'clips:auto-shorts
                            {qaris* : Qari ids}
                            {--count= : Shorts per qari (defaults to config clips.auto.per_qari)}
                            {--user= : Owner user id (defaults to the first admin)}';

    protected $description = 'Queue mosque-background shorts with Quran subtitles for each qari';

    public function handle(AutoShortQueue $queue): int
    {
        $count = (int) ($this->option('count') ?: config('clips.auto.per_qari'));
        $owner = $this->option('user')
            ? User::find((int) $this->option('user'))
            : User::role('admin')->orderBy('id')->first();

        if ($owner === null) {
            $this->error('No owner user found. Pass --user.');

            return self::FAILURE;
        }

        foreach ($this->argument('qaris') as $qariId) {
            $qari = Qari::find((int) $qariId);

            if ($qari === null) {
                $this->warn("Qari {$qariId} not found, skipped.");

                continue;
            }

            $clips = $queue->queueForQari($qari, $count, $owner);

            $this->info("{$qari->name_en}: {$clips->count()} shorts queued.");
        }

        return self::SUCCESS;
    }
}
