<?php
namespace App\Policies;
use App\Models\{Qari,User};
class QariPolicy {
    public function viewAny(?User $u): bool { return true; }
    public function view(?User $u,Qari $q): bool { return $q->status==='active'||($u&&($u->hasRole('admin')||$q->created_by===$u->id)); }
    public function create(User $u): bool { return $u->hasAnyRole(['admin','creator']); }
    public function update(User $u,Qari $q): bool { return $u->hasRole('admin')||$q->created_by===$u->id; }
    public function delete(User $u,Qari $q): bool { return $u->hasRole('admin')||$q->created_by===$u->id; }
}
