<?php

namespace App\Actions\Item;

use App\Data\Api\Item\ItemUpdateData;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateItemAction
{
    public function handle(Item $item, ItemUpdateData $data): Item
    {
        return DB::transaction(function () use ($item, $data): Item {
            if ($data->parent_id !== null) {
                $this->ensureParentWithinWorkspace($data->parent_id, $item->workspace_id);
                $item->parent_id = $data->parent_id;
            }

            if ($data->type !== null) {
                $item->type = $data->type;
            }

            if ($data->title !== null) {
                $item->title = $data->title;
            }

            if ($data->data !== null) {
                $item->data = $data->data;
            }

            if ($data->sort_order !== null) {
                $item->sort_order = $data->sort_order;
            }

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
