<?php

namespace App\Policies;

use App\Models\TilawatSource;
use App\Models\User;

class TilawatSourcePolicy
{
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'creator']);
    }

    public function update(User $user, TilawatSource $source): bool
    {
        return $user->hasRole('admin') || $source->created_by === $user->id;
    }

    public function delete(User $user, TilawatSource $source): bool
    {
        return $user->hasRole('admin') || $source->created_by === $user->id;
    }
}
