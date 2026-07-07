<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;

/**
 * @group Workspace Management
 *
 * Admin-only endpoints for managing all users' workspaces.
 */
final class WorkspaceGeneralShowController extends Controller
{
    /**
     * Get a specific workspace (admin or owner).
     *
     * @authenticated
     *
     * @urlParam workspace integer required The ID of the workspace. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "name": "Workspace",
     *   "settings": null,
     *   "created_at": "2026-07-08T12:00:00.000000Z",
     *   "updated_at": "2026-07-08T12:00:00.000000Z"
     * }
     */
    public function __invoke(Workspace $workspace): WorkspaceData
    {
        Gate::authorize('view', $workspace);

        return WorkspaceData::fromModel($workspace);
    }
}
