<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SocialCampaign;
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
    public function poster(string $post, SocialCampaign $campaign): BinaryFileResponse
    {
        return response()->file($this->posterPath($post, $campaign));
    }

    public function downloadPoster(string $post, SocialCampaign $campaign): BinaryFileResponse
    {
        $path = $this->posterPath($post, $campaign);

        return response()->download($path, basename($path));
    }

    private function posterPath(string $post, SocialCampaign $campaign): string
    {
        $found = $campaign->find($post);

        abort_unless($found !== null && $found['image_ready'], 404);

        return $found['image_path'];
    }
}
