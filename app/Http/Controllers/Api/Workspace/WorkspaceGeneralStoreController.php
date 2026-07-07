<?php

namespace App\Http\Controllers\Api\Workspace;

use App\Actions\Workspace\CreateWorkspaceAction;
use App\Data\Api\Workspace\WorkspaceStoreData;
use App\Data\Api\WorkspaceData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Workspace Management
 *
 * Admin-only endpoints for managing all users' workspaces.
 */
final class WorkspaceGeneralStoreController extends Controller
{
    public function __construct(private readonly CreateWorkspaceAction $createWorkspace) {}

    /**
     * Create a workspace for any user (admin only).
     *
     * @authenticated
     *
     * @bodyParam user_id integer required The owning user's ID. Example: 1
     * @bodyParam name string required The workspace name (max 120 chars). Example: Acme Workspace
     * @bodyParam settings object optional Arbitrary workspace settings JSON object.
     *
     * @response 201 {
     *   "data": {"id":2,"user_id":1,"name":"Acme Workspace","settings":null,"created_at":"2026-07-08T12:00:00.000000Z","updated_at":"2026-07-08T12:00:00.000000Z"}
     * }
     */
    public function __invoke(WorkspaceStoreData $data, Request $request): JsonResponse
    {
        $workspace = $this->createWorkspace->handle($data->user_id, $data);

        return WorkspaceData::fromModel($workspace)
            ->toResponse($request)
            ->setStatusCode(201);
    }
}
