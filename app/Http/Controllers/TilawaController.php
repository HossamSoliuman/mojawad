<?php
namespace App\Http\Controllers;
use App\Models\{DownloadLog,Tilawa};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class TilawaController extends Controller {
    public function show(Tilawa $tilawa) {
        abort_if($tilawa->status !== 'active' && !auth()->user()?->hasRole('admin'), 403);
        $tilawa->load('qari');
        $related = Tilawa::where('qari_id',$tilawa->qari_id)->where('id','!=',$tilawa->id)->where('status','active')->take(6)->get();
        $liked   = auth()->check() ? \App\Models\Like::where('user_id',auth()->id())->where('tilawa_id',$tilawa->id)->exists() : false;
        $saved   = auth()->check() ? \App\Models\SavedTilawa::where('user_id',auth()->id())->where('tilawa_id',$tilawa->id)->exists() : false;
        return view('pages.tilawa.show', compact('tilawa','related','liked','saved'));
    }
    public function download(Request $request, Tilawa $tilawa) {
        abort_if($tilawa->status !== 'active', 403);
        DownloadLog::create(['user_id'=>auth()->id(),'tilawa_id'=>$tilawa->id,'ip_address'=>$request->ip()]);
        $tilawa->increment('downloads_count');
        return Storage::disk('public')->download($tilawa->audio_path, Str::slug($tilawa->title).'.mp3');
    }
}
