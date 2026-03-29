<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCreatorApplicationRequest;
use App\Models\CreatorApplication;

class CreatorApplicationController extends Controller
{
    public function create()
    {
        $application = auth()->user()->creatorApplication;

        if ($application?->isPending()) {
            return redirect()->route('home')->with('info', 'Your application is already under review.');
        }

        if (auth()->user()->canModerate()) {
            return redirect()->route('home')->with('info', 'You already have creator access.');
        }

        return view('creator.apply');
    }

    public function store(StoreCreatorApplicationRequest $request)
    {
        CreatorApplication::updateOrCreate(
            ['user_id' => auth()->id()],
            ['reason' => $request->validated('reason'), 'status' => 'pending', 'reviewed_by' => null]
        );

        return redirect()->route('home')->with('success', 'Application submitted.');
    }
}
