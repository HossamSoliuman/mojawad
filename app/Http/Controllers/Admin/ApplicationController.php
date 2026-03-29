<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreatorApplication;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = CreatorApplication::pending()->with('user')->latest()->paginate(20);
        return view('admin.applications.index', compact('applications'));
    }

    public function approve(CreatorApplication $application)
    {
        $application->update(['status' => 'approved', 'reviewed_by' => auth()->id()]);
        $application->user->update(['role' => 'creator']);
        return back()->with('success', 'Application approved. User is now a Creator.');
    }

    public function reject(CreatorApplication $application)
    {
        $application->update(['status' => 'rejected', 'reviewed_by' => auth()->id()]);
        return back()->with('success', 'Application rejected.');
    }
}
