<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedTilawa;
use App\Models\Tilawa;

class SaveController extends Controller
{
    public function toggle(Tilawa $tilawa)
    {
        $existing = SavedTilawa::where('user_id', auth()->id())
            ->where('tilawa_id', $tilawa->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            SavedTilawa::create(['user_id' => auth()->id(), 'tilawa_id' => $tilawa->id]);
            $saved = true;
        }

        return response()->json(['saved' => $saved]);
    }
}
