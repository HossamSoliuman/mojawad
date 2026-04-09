<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\{SavedTilawa,Tilawa};
use Illuminate\Http\Request;
class SaveController extends Controller {
    public function toggle(Request $request, Tilawa $tilawa) {
        if (!auth()->check()) return response()->json(['error'=>'Unauthenticated'],401);
        $existing=SavedTilawa::where('user_id',auth()->id())->where('tilawa_id',$tilawa->id)->first();
        if ($existing) { $existing->delete(); $saved=false; }
        else           { SavedTilawa::create(['user_id'=>auth()->id(),'tilawa_id'=>$tilawa->id]); $saved=true; }
        return response()->json(['saved'=>$saved]);
    }
}
