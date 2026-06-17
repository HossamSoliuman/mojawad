<?php

namespace App\Services;

use App\Models\TmpUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    private string $disk = 'public';

    private string $uploadDriver;

    public function __construct()
    {
        $this->uploadDriver = config('filesystems.upload_driver', 'public');
    }

    public function moveFromTmp(string $token, string $directory): string
    {
        $tmp = TmpUpload::findOrFail($token);
        $ext = pathinfo($tmp->original_name, PATHINFO_EXTENSION);
        $filename = Str::random(32).($ext ? '.'.$ext : '');
        $newPath = $directory.'/'.$filename;

        Storage::disk($tmp->disk)->move($tmp->path, $newPath);
        $tmp->delete();

        return $newPath;
    }

    public function uploadAudio(UploadedFile $file, string $title): string
    {
        $filename = Str::slug($title).'-'.time().'.mp3';

        return $this->store($file, 'tilawat', $filename);
    }

    public function uploadCover(UploadedFile $file): string
    {
        $filename = Str::random(32).'.'.$file->getClientOriginalExtension();

        return $this->store($file, 'tilawa-covers', $filename);
    }

    public function delete(?string $path): bool
    {
        if (! $path) {
            return true;
        }

        return match ($this->uploadDriver) {
            's3' => $this->deleteS3($path),
            default => Storage::disk($this->disk)->delete($path),
        };
    }

    private function store(UploadedFile $file, string $directory, string $filename): string
    {
        return match ($this->uploadDriver) {
            's3' => $this->storeS3($file, $directory, $filename),
            default => $file->storeAs($directory, $filename, $this->disk),
        };
    }

    private function storeS3(UploadedFile $file, string $directory, string $filename): string
    {
        return Storage::disk('s3')->putFileAs(
            $directory,
            $file,
            $filename,
            'public'
        );
    }

    private function deleteS3(string $path): bool
    {
        return Storage::disk('s3')->delete($path);
    }
}
