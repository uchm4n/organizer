<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

final class CurrentUserController extends Controller
{
    public function __invoke(Request $request): UserData
    {
        /** @var User $user */
        $user = $request->user();

        return UserData::fromModel($user);
    }
}
