<?php

namespace App\Actions\Item;

use App\Data\Api\Item\ItemStoreData;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateItemAction
{
    public function handle(ItemStoreData $data, User $actor): Item
    {
        return DB::transaction(function () use ($data): Item {
            $this->ensureParentWithinWorkspace($data->parent_id, $data->workspace_id);

            $item               = new Item;
            $item->workspace_id = $data->workspace_id;
            $item->parent_id    = $data->parent_id;
            $item->type         = $data->type;
            $item->title        = $data->title;
            $item->data         = $data->data;
            $item->sort_order   = $data->sort_order;
            $item->save();

            return $item->fresh();
        });
    }

    private function ensureParentWithinWorkspace(?int $parentId, int $workspaceId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Item::query()->where('id', $parentId)->first();

        if ($parent === null || $parent->workspace_id !== $workspaceId) {
            throw ValidationException::withMessages([
                'parent_id' => [__('This parent item does not belong to the selected workspace.')],
            ]);
        }
    }
}
