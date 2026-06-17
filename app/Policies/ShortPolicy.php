<?php

namespace App\Policies;

use App\Models\Short;
use App\Models\User;

class ShortPolicy
{
    public function viewAny(?User $u): bool
    {
        return true;
    }

    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'creator']);
    }

    public function update(User $u, Short $s): bool
    {
        return $u->hasRole('admin') || $s->created_by === $u->id;
    }

    public function delete(User $u, Short $s): bool
    {
        return $u->hasRole('admin') || $s->created_by === $u->id;
    }
}
