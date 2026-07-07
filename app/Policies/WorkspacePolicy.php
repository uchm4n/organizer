<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->hasRole(Role::Admin) || $user->is($workspace->user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Admin);
    }

    public function update(User $user, Workspace $workspace): Response
    {
        return $user->hasRole(Role::Admin) || $user->is($workspace->user)
            ? Response::allow()
            : Response::deny('You do not own this workspace.');
    }

    public function delete(User $user, Workspace $workspace): Response
    {
        return $user->hasRole(Role::Admin) || $user->is($workspace->user)
            ? Response::allow()
            : Response::deny('You do not own this workspace.');
    }
}
