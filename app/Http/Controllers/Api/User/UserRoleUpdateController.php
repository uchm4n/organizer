<?php

namespace App\Http\Controllers\Api\User;

use App\Actions\User\UpdateUserRoleAction;
use App\Data\Api\User\UpdateUserRoleData;
use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;

final class UserRoleUpdateController extends Controller
{
    public function __construct(private readonly UpdateUserRoleAction $updateUserRole) {}

    public function __invoke(UpdateUserRoleData $data, User $user): UserData
    {
        $updated = $this->updateUserRole->handle($user, $data->role);

        return UserData::fromModel($updated);
    }
}
