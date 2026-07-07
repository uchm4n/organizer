<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * @group Workspace Management
 *
 * Admin-only endpoints for managing all users' workspaces.
 */
final class WorkspaceIndexController extends Controller
{
    private const int DefaultPerPage = 50;

    /**
     * List all workspaces (admin only).
     *
     * @authenticated
     *
     * @queryParam per_page integer optional Items per page (default 10). Example: 10
     *
     * @response 200 {
     *   "data": [{"id":1,"name":"Workspace","settings":null,"created_at":null,"updated_at":null}],
     *   "links": {"first":"...","last":"...","prev":null,"next":null},
     *   "meta": {"current_page":1,"per_page":10,"total":1}
     * }
     */
    public function __invoke(Request $request): PaginatedDataCollection
    {
        $perPage = $request->integer('per_page', self::DefaultPerPage);
        $query   = Workspace::query();

        return WorkspaceData::collect(
            $query->paginate($perPage),
            PaginatedDataCollection::class,
        );
    }
}
