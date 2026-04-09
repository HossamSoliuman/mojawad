<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{Qari,Tilawa};
use Illuminate\Http\Request;
class SearchController extends Controller {
    public function __invoke(Request $request) {
        $q = trim($request->get('q',''));
        if (strlen($q) < 2) return response()->json(['qaris'=>[],'tilawat'=>[]]);
        $qaris   = Qari::where('status','active')->where('name','like','%'.$q.'%')->take(4)->get(['id','name','slug'])->map(fn($r)=>['id'=>$r->id,'name'=>$r->name,'url'=>route('qaris.show',$r->slug)]);
        $tilawat = Tilawa::with('qari:id,name')->where('status','active')->where('title','like','%'.$q.'%')->take(4)->get()->map(fn($r)=>['id'=>$r->id,'title'=>$r->title,'qari'=>$r->qari->name,'url'=>route('tilawa.show',$r->slug)]);
        return response()->json(['qaris'=>$qaris,'tilawat'=>$tilawat]);
    }
}
