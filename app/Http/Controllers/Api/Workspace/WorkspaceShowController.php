<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Actions\Workspace\UpsertWorkspaceAction;
use App\Data\Api\Workspace\WorkspaceUpsertData;
use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @group Workspace Management
 *
 * Endpoints for the authenticated user's own workspace.
 */
final class WorkspaceShowController extends Controller
{
    public function __construct(private readonly UpsertWorkspaceAction $upsertWorkspace) {}

    /**
     * Get (or create) the authenticated user's workspace.
     *
     * On first access, a default workspace is created for the user.
     *
     * @authenticated
     */
    public function __invoke(Request $request): WorkspaceData
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $this->upsertWorkspace->handle($user, new WorkspaceUpsertData);

        return WorkspaceData::fromModel($workspace);
    }
}
