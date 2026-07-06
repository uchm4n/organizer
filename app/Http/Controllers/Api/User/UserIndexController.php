<?php

namespace App\Http\Controllers\Api\User;

use App\Data\Api\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * @group User Management
 *
 * Admin-only endpoints for managing users.
 */
final class UserIndexController extends Controller
{
    private const int DefaultPerPage = 10;

    /**
     * List users (paginated).
     *
     * Requires the `admin` role. Pass `?per_page=` to control page size (default 10).
     *
     * @authenticated
     *
     * @queryParam per_page integer Number of users per page. Example: 20
     */
    public function __invoke(Request $request): PaginatedDataCollection
    {
        $perPage = $request->integer('per_page', self::DefaultPerPage);

        return UserData::collect(
            User::query()->paginate($perPage),
            PaginatedDataCollection::class,
        );
    }
}
