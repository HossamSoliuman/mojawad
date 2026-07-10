<?php

namespace App\Policies;

use App\Models\Publication;
use App\Models\User;

class PublicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'creator']);
    }

    public function view(User $user, Publication $publication): bool
    {
        return $user->hasRole('admin') || $publication->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'creator']);
    }

    public function update(User $user, Publication $publication): bool
    {
        return $user->hasRole('admin') || $publication->created_by === $user->id;
    }

    public function delete(User $user, Publication $publication): bool
    {
        return $user->hasRole('admin') || $publication->created_by === $user->id;
    }
}
