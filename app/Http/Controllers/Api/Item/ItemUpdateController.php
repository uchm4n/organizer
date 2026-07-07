<?php

namespace App\Http\Controllers\Api\Item;

use App\Actions\Item\UpdateItemAction;
use App\Data\Api\Item\ItemUpdateData;
use App\Data\Api\ItemData;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Support\Facades\Gate;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemUpdateController extends Controller
{
    public function __construct(private readonly UpdateItemAction $updateItem) {}

    /**
     * Update an item.
     *
     * @authenticated
     *
     * @urlParam item integer required The ID of the item. Example: 1
     *
     * @bodyParam parent_id integer optional The parent item ID (must share the workspace).
     * @bodyParam type integer optional The ItemType enum value.
     * @bodyParam title string optional The item title (max 255 chars).
     * @bodyParam data object optional Arbitrary item data JSON object.
     * @bodyParam sort_order integer optional Sort weight.
     *
     * @response 200 {
     *   "id": 1,
     *   "workspace_id": 1,
     *   "parent_id": null,
     *   "type": 1,
     *   "title": "Renamed Note",
     *   "data": {},
     *   "sort_order": 5,
     *   "created_at": "2026-07-08T12:00:00.000000Z",
     *   "updated_at": "2026-07-08T12:01:00.000000Z",
     *   "deleted_at": null
     * }
     */
    public function __invoke(ItemUpdateData $data, Item $item): ItemData
    {
        Gate::authorize('update', $item);

        $updated = $this->updateItem->handle($item, $data);

        return ItemData::fromModel($updated);
    }
}
