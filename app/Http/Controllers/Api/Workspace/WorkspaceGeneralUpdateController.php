<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Actions\Workspace\UpdateWorkspaceAction;
use App\Data\Api\Workspace\WorkspaceUpdateData;
use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;

/**
 * @group Workspace Management
 *
 * Admin-only endpoints for managing all users' workspaces.
 */
final class WorkspaceGeneralUpdateController extends Controller
{
    public function __construct(private readonly UpdateWorkspaceAction $updateWorkspace) {}

    /**
     * Update any user's workspace (admin or owner).
     *
     * @authenticated
     *
     * @urlParam workspace integer required The ID of the workspace. Example: 1
     *
     * @bodyParam name string optional The workspace name (max 120 chars). Example: Renamed
     * @bodyParam settings object optional Arbitrary workspace settings JSON object.
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "name": "Renamed",
     *   "settings": null,
     *   "created_at": "2026-07-08T12:00:00.000000Z",
     *   "updated_at": "2026-07-08T12:01:00.000000Z"
     * }
     */
    public function __invoke(WorkspaceUpdateData $data, Workspace $workspace): WorkspaceData
    {
        Gate::authorize('update', $workspace);

        $updated = $this->updateWorkspace->handle($workspace, $data);

        return WorkspaceData::fromModel($updated);
    }
}
