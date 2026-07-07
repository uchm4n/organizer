<?php

namespace App\Http\Controllers\Api\Item;

use App\Data\Api\ItemData;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Support\Facades\Gate;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemShowController extends Controller
{
    /**
     * Get a specific item.
     *
     * @authenticated
     *
     * @urlParam item integer required The ID of the item. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "workspace_id": 1,
     *   "parent_id": null,
     *   "type": 1,
     *   "title": "Note",
     *   "data": {},
     *   "sort_order": 0,
     *   "created_at": "2026-07-08T12:00:00.000000Z",
     *   "updated_at": "2026-07-08T12:00:00.000000Z",
     *   "deleted_at": null
     * }
     * @response 404 {"title":"Not Found","status":404,"detail":"Not found."}
     */
    public function __invoke(Item $item): ItemData
    {
        Gate::authorize('view', $item);

        return ItemData::fromModel($item);
    }
}
