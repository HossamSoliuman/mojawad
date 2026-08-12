<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookPost;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SocialController extends Controller
{
    public function facebook(): View
    {
        return view('admin.social.facebook');
    }

    /**
     * Stream the generated image so the side card can preview it — the images
     * folder sits outside the public root.
     */
    public function poster(FacebookPost $post): BinaryFileResponse
    {
        return response()->file($this->posterPath($post));
    }

    public function downloadPoster(FacebookPost $post): BinaryFileResponse
    {
        $path = $this->posterPath($post);

        return response()->download($path, basename($path));
    }

    private function posterPath(FacebookPost $post): string
    {
        abort_unless($post->hasImage(), 404);

        return $post->imagePath();
    }
}
