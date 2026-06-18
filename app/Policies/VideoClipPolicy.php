<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VideoClip;

class VideoClipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'creator']);
    }

    public function view(User $user, VideoClip $videoClip): bool
    {
        return $user->hasRole('admin') || $videoClip->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'creator']);
    }

    public function update(User $user, VideoClip $videoClip): bool
    {
        return $user->hasRole('admin') || $videoClip->created_by === $user->id;
    }

    public function delete(User $user, VideoClip $videoClip): bool
    {
        return $user->hasRole('admin') || $videoClip->created_by === $user->id;
    }
}
