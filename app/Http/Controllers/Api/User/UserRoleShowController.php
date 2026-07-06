<?php

namespace App\Http\Controllers\Api\User;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * @group User Management
 *
 * Admin-only endpoints for inspecting a user's role.
 */
final class UserRoleShowController extends Controller
{
    /**
     * Get a user's role.
     *
     * Requires the `admin` role.
     *
     * @authenticated
     *
     * @urlParam user integer required The ID of the user. Example: 1
     */
    public function __invoke(User $user): UserData
    {
        return UserData::fromModel($user);
    }
}
