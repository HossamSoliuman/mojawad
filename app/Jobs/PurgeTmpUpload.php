<?php

namespace App\Jobs;

use App\Models\TmpUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class PurgeTmpUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    public function handle(): void
    {
        $tmp = TmpUpload::find($this->token);

        if (!$tmp) {
            return;
        }

        Storage::disk($tmp->disk)->delete($tmp->path);
        $tmp->delete();
    }
}
