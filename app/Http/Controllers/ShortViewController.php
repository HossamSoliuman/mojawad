<?php

namespace App\Http\Controllers;

use App\Models\Short;
use App\Services\ShortSelector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShortViewController extends Controller
{
    public function __construct(private ShortSelector $selector) {}

    public function __invoke(Request $request, Short $short): Response
    {
        if ($short->status === 'active' && $short->media_path) {
            $this->selector->recordView($short, $this->selector->viewerKey($request));
        }

        return response()->noContent();
    }
}
