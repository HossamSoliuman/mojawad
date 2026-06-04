<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(): View
    {
        return view('admin.review.index');
    }

    public function history(): View
    {
        return view('admin.review.history');
    }
}
