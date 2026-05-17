<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PurgeTmpUpload;
use App\Models\TmpUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TmpUploadController extends Controller
{
    private array $typeRules = [
        'audio'  => ['mimes:mp3,mpeg,ogg,wav', 'max:204800'],
        'cover'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        'image'  => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ];

    public function store(Request $request)
    {
        $type = $request->query('type', 'audio');

        if (!array_key_exists($type, $this->typeRules)) {
            return response('Invalid type.', 422);
        }

        $rules = array_merge(['file' => 'required|file'], ['file' => array_merge(['required', 'file'], $this->typeRules[$type])]);
        $request->validate(['file' => array_merge(['required', 'file'], $this->typeRules[$type])]);

        $file   = $request->file('file');
        $token  = (string) Str::uuid();
        $dir    = 'tmp/uploads/' . Auth::id();
        $name   = $token . '.' . $file->getClientOriginalExtension();

        $file->storeAs($dir, $name, 'public');

        TmpUpload::create([
            'id'            => $token,
            'disk'          => 'public',
            'path'          => $dir . '/' . $name,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
            'uploaded_by'   => Auth::id(),
            'expires_at'    => now()->addHours(2),
        ]);

        PurgeTmpUpload::dispatch($token)->delay(now()->addHours(2));

        return response($token, 200)->header('Content-Type', 'text/plain');
    }

    public function destroy(string $token)
    {
        $tmp = TmpUpload::find($token);

        if (!$tmp || $tmp->uploaded_by !== Auth::id()) {
            return response('', 200);
        }

        Storage::disk($tmp->disk)->delete($tmp->path);
        $tmp->delete();

        return response('', 200);
    }
}
