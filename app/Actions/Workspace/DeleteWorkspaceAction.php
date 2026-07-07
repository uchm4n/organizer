<?php

namespace App\Actions\Workspace;

use App\Models\Workspace;

class DeleteWorkspaceAction
{
    public function handle(Workspace $workspace): void
    {
        $workspace->delete();
    }
}
