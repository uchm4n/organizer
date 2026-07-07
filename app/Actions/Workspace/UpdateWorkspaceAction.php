<?php

namespace App\Actions\Workspace;

use App\Data\Api\Workspace\WorkspaceUpdateData;
use App\Models\Workspace;

class UpdateWorkspaceAction
{
    public function handle(Workspace $workspace, WorkspaceUpdateData $data): Workspace
    {
        if ($data->name !== null) {
            $workspace->name = $data->name;
        }

        if ($data->settings !== null) {
            $workspace->settings = $data->settings;
        }

        $workspace->save();

        return $workspace->fresh();
    }
}
