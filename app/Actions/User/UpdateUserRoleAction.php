<?php

namespace App\Actions\User;

use App\Enums\Role;
use App\Models\User;

/**
 * Change a user's assigned role. HTTP-agnostic so it can be invoked from
 * the admin API endpoint, a future console command, or a job, without
 * coupling to the request lifecycle.
 */
class UpdateUserRoleAction
{
    public function handle(User $target, Role $role): User
    {
        $target->role = $role;
        $target->save();

        return $target->fresh();
    }
}
