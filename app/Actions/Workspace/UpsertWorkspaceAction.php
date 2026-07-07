<?php

namespace App\Actions\Workspace;

use App\Data\Api\Workspace\WorkspaceUpsertData;
use App\Models\User;
use App\Models\Workspace;

class UpsertWorkspaceAction
{
    public function handle(User $user, WorkspaceUpsertData $data): Workspace
    {
        $workspace = $user->workspace()->firstOrCreate([]);

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
