<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class ItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Item $item): Response
    {
        return $this->allowIfOwnerOrAdmin($user, $item);
    }

    public function create(User $user, int $workspaceId): Response
    {
        if ($user->hasRole(Role::Admin)) {
            return Response::allow();
        }

        $workspace = Workspace::find($workspaceId);

        return $workspace && $user->is($workspace->user)
            ? Response::allow()
            : Response::deny('You do not own this workspace.');
    }

    public function update(User $user, Item $item): Response
    {
        return $this->allowIfOwnerOrAdmin($user, $item);
    }

    public function delete(User $user, Item $item): Response
    {
        return $this->allowIfOwnerOrAdmin($user, $item);
    }

    public function restore(User $user, Item $item): Response
    {
        return $this->allowIfOwnerOrAdmin($user, $item);
    }

    private function allowIfOwnerOrAdmin(User $user, Item $item): Response
    {
        return $user->hasRole(Role::Admin) || $user->is($item->workspace->user)
            ? Response::allow()
            : Response::deny('You do not own this item.');
    }
}
