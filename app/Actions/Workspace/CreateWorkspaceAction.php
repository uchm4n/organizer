<?php

namespace App\Actions\Workspace;

use App\Data\Api\Workspace\WorkspaceStoreData;
use App\Models\Workspace;
use Illuminate\Validation\ValidationException;

class CreateWorkspaceAction
{
    public function handle(int $userId, WorkspaceStoreData $data): Workspace
    {
        if (Workspace::query()->where('user_id', $userId)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('A workspace already exists for this user.')],
            ]);
        }

        $workspace           = new Workspace;
        $workspace->user_id  = $userId;
        $workspace->name     = $data->name;
        $workspace->settings = $data->settings;
        $workspace->save();

        return $workspace->fresh();
    }
}
