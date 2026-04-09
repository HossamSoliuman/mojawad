<?php

namespace App\Policies;

use App\Models\{Tilawa, User};

class TilawaPolicy
{
    public function viewAny(?User $u): bool
    {
        return true;
    }
    public function view(?User $u, Tilawa $t): bool
    {
        return $t->status === 'active' || ($u && ($u->hasRole('admin') || $t->uploaded_by === $u->id));
    }
    public function create(User $u): bool
    {
        return $u->hasAnyRole(['admin', 'creator']);
    }
    public function update(User $u, Tilawa $t): bool
    {
        return $u->hasRole('admin') || $t->uploaded_by === $u->id;
    }
    public function delete(User $u, Tilawa $t): bool
    {
        return $u->hasRole('admin') || $t->uploaded_by === $u->id;
    }
}
