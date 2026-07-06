<?php

namespace App\Http\Controllers\Api\User;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;

final class UserRoleShowController extends Controller
{
    public function __invoke(User $user): UserData
    {
        return UserData::fromModel($user);
    }
}
