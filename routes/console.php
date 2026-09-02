<?php

use App\Console\Commands\CleanTmpUploads;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(CleanTmpUploads::class)->hourly();

/*
 * Facebook publishing is no longer scheduled: posts go out by hand from the
 * publishing workspace. The `facebook:publish-due` command is still there to
 * be run manually if the automatic queue is ever brought back.
 */
