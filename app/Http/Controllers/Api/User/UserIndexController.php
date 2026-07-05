<?php

namespace App\Http\Controllers\Api\User;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

final class UserIndexController extends Controller
{
    private const int DefaultPerPage = 10;

    public function __invoke(Request $request): PaginatedDataCollection
    {
        $perPage = $request->integer('per_page', self::DefaultPerPage);

        return UserData::collect(
            User::query()->paginate($perPage),
            PaginatedDataCollection::class,
        );
    }
}
