<?php

namespace App\Http\Controllers\Api\User;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @group User Management
 *
 * Endpoints for the currently authenticated user.
 */
final class UserShowController extends Controller
{
    /**
     * Get the authenticated user.
     *
     * @authenticated
     */
    public function __invoke(Request $request): UserData
    {
        /** @var User $user */
        $user = $request->user();

        return UserData::fromModel($user);
    }
}
