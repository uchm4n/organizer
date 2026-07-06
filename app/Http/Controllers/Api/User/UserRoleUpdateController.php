<?php

namespace App\Http\Controllers\Api\User;

use App\Actions\User\UpdateUserRoleAction;
use App\Data\Api\User\UpdateUserRoleData;
use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * @group User Management
 *
 * Admin-only endpoints for changing a user's role.
 */
final class UserRoleUpdateController extends Controller
{
    public function __construct(private readonly UpdateUserRoleAction $updateUserRole) {}

    /**
     * Update a user's role.
     *
     * Requires the `admin` role.
     *
     * @authenticated
     *
     * @urlParam user integer required The ID of the user. Example: 1
     */
    public function __invoke(UpdateUserRoleData $data, User $user): UserData
    {
        $updated = $this->updateUserRole->handle($user, $data->role);

        return UserData::fromModel($updated);
    }
}
