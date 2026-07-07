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
final class WorkspaceStoreController extends Controller
{
    public function __construct(private readonly UpsertWorkspaceAction $upsertWorkspace) {}

    /**
     * Create or update the authenticated user's workspace.
     *
     * @authenticated
     *
     * @bodyParam name string optional The workspace name (max 120 chars). Example: My Workspace
     * @bodyParam settings object optional Arbitrary workspace settings JSON object. Example: {"theme":"dark"}
     *
     * @response 200 {
     *   "data": {"id":1,"user_id":1,"name":"My Workspace","settings":{"theme":"dark"},"created_at":"2026-07-08T12:00:00.000000Z","updated_at":"2026-07-08T12:00:00.000000Z"}
     * }
     */
    public function __invoke(WorkspaceUpsertData $data, Request $request): WorkspaceData
    {
        /** @var User $user */
        $user = $request->user();

        $workspace = $this->upsertWorkspace->handle($user, $data);

        return WorkspaceData::fromModel($workspace);
    }
}
