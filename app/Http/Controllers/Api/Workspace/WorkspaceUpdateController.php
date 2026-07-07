<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Actions\Workspace\UpdateWorkspaceAction;
use App\Data\Api\Workspace\WorkspaceUpdateData;
use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @group Workspace Management
 *
 * Endpoints for the authenticated user's own workspace.
 */
final class WorkspaceUpdateController extends Controller
{
    public function __construct(private readonly UpdateWorkspaceAction $updateWorkspace) {}

    /**
     * Update the authenticated user's workspace.
     *
     * @authenticated
     *
     * @bodyParam name string optional The workspace name (max 120 chars). Example: Renamed
     * @bodyParam settings object optional Arbitrary workspace settings JSON object.
     *
     * @response 200 {
     *   "id": 1,
     *   "user_id": 1,
     *   "name": "Renamed",
     *   "settings": {"theme":"dark"},
     *   "created_at": "2026-07-08T12:00:00.000000Z",
     *   "updated_at": "2026-07-08T12:01:00.000000Z"
     * }
     */
    public function __invoke(WorkspaceUpdateData $data, Request $request): WorkspaceData
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $user->workspace()->firstOrFail();

        $updated = $this->updateWorkspace->handle($workspace, $data);

        return WorkspaceData::fromModel($updated);
    }
}
