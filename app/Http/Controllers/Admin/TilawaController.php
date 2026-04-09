<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Qari,Tilawa};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,Storage};
use Illuminate\Support\Str;
class TilawaController extends Controller {
    public function index(Request $request) {
        $tilawat=Tilawa::with('qari')->when($request->search,fn($q)=>$q->where('title','like','%'.$request->search.'%'))->when($request->status,fn($q)=>$q->where('status',$request->status))->latest()->paginate(15)->withQueryString();
        return view('admin.tilawat.index',compact('tilawat'));
    }
    public function create() { $qaris=Qari::where('status','active')->orderBy('name')->get(['id','name']); return view('admin.tilawat.create',compact('qaris')); }
    public function store(Request $request) {
        $request->validate(['qari_id'=>'required|exists:qaris,id','title'=>'required|string|max:255','description'=>'nullable|string','recorded_at'=>'nullable|date','recorded_place'=>'nullable|string|max:255','status'=>'required|in:active,inactive,pending','audio'=>'required|file|mimes:mp3,mpeg,ogg,wav|max:204800','cover_image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $slug=Str::slug($request->title); if(Tilawa::where('slug',$slug)->exists()) $slug.='-'.Str::random(4);
        $dur=0; try{ $o=shell_exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.escapeshellarg($request->file('audio')->getRealPath()).' 2>/dev/null'); if($o&&is_numeric(trim($o)))$dur=(int)round((float)trim($o)); }catch(\Throwable){}
        Tilawa::create(['qari_id'=>$request->qari_id,'title'=>$request->title,'slug'=>$slug,'description'=>$request->description??null,'recorded_at'=>$request->recorded_at??null,'recorded_place'=>$request->recorded_place??null,'audio_path'=>$request->file('audio')->store('tilawat','public'),'duration'=>$dur,'cover_image'=>$request->hasFile('cover_image')?$request->file('cover_image')->store('tilawa-covers','public'):null,'status'=>$request->status,'is_featured'=>$request->boolean('is_featured'),'uploaded_by'=>auth()->id()]);
        Cache::forget('homepage_data');
        return redirect()->route('admin.tilawat.index')->with('success','Tilawa created successfully.');
    }
    public function edit(Tilawa $tilawa) { $qaris=Qari::where('status','active')->orderBy('name')->get(['id','name']); return view('admin.tilawat.edit',compact('tilawa','qaris')); }
    public function update(Request $request, Tilawa $tilawa) {
        $request->validate(['qari_id'=>'required|exists:qaris,id','title'=>'required|string|max:255','description'=>'nullable|string','recorded_at'=>'nullable|date','recorded_place'=>'nullable|string|max:255','status'=>'required|in:active,inactive,pending','audio'=>'nullable|file|mimes:mp3,mpeg,ogg,wav|max:204800','cover_image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $u=['qari_id'=>$request->qari_id,'title'=>$request->title,'description'=>$request->description??null,'recorded_at'=>$request->recorded_at??null,'recorded_place'=>$request->recorded_place??null,'status'=>$request->status,'is_featured'=>$request->boolean('is_featured')];
        if($request->hasFile('audio')){ Storage::disk('public')->delete($tilawa->audio_path); $u['audio_path']=$request->file('audio')->store('tilawat','public'); try{ $o=shell_exec('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '.escapeshellarg($request->file('audio')->getRealPath()).' 2>/dev/null'); if($o&&is_numeric(trim($o)))$u['duration']=(int)round((float)trim($o)); }catch(\Throwable){} }
        if($request->hasFile('cover_image')){ if($tilawa->cover_image)Storage::disk('public')->delete($tilawa->cover_image); $u['cover_image']=$request->file('cover_image')->store('tilawa-covers','public'); }
        $tilawa->update($u); Cache::forget('homepage_data');
        return redirect()->route('admin.tilawat.index')->with('success','Tilawa updated successfully.');
    }
    public function destroy(Tilawa $tilawa) {
        Storage::disk('public')->delete($tilawa->audio_path); if($tilawa->cover_image)Storage::disk('public')->delete($tilawa->cover_image);
        $tilawa->delete(); Cache::forget('homepage_data');
        return redirect()->route('admin.tilawat.index')->with('success','Tilawa deleted.');
    }
}
