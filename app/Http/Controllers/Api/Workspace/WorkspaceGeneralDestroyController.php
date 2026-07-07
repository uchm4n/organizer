<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Actions\Workspace\DeleteWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * @group Workspace Management
 *
 * Admin-only endpoints for managing all users' workspaces.
 */
final class WorkspaceGeneralDestroyController extends Controller
{
    public function __construct(private readonly DeleteWorkspaceAction $deleteWorkspace) {}

    /**
     * Delete a workspace (admin or owner).
     *
     * Deleting a workspace cascades to all of its items.
     *
     * @authenticated
     *
     * @urlParam workspace integer required The ID of the workspace. Example: 1
     *
     * @response 204 null
     */
    public function __invoke(Workspace $workspace): JsonResponse
    {
        Gate::authorize('delete', $workspace);

        $this->deleteWorkspace->handle($workspace);

        return response()->json(null, 204);
    }
}
