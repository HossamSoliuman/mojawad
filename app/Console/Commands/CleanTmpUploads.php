<?php

namespace App\Console\Commands;

use App\Models\TmpUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanTmpUploads extends Command
{
    protected $signature   = 'uploads:clean-tmp';
    protected $description = 'Delete expired temporary FilePond uploads from storage and database';

    public function handle(): int
    {
        $expired = TmpUpload::expired()->get();
        $count   = 0;

        foreach ($expired as $tmp) {
            Storage::disk($tmp->disk)->delete($tmp->path);
            $tmp->delete();
            $count++;
        }

        $this->info("Cleaned {$count} expired temp upload(s).");

        return Command::SUCCESS;
    }
}
