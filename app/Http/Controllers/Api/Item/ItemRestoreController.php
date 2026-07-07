<?php

namespace App\Http\Controllers\Api\Item;

use App\Actions\Item\RestoreItemAction;
use App\Data\Api\ItemData;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemRestoreController extends Controller
{
    public function __construct(private readonly RestoreItemAction $restoreItem) {}

    /**
     * Restore a soft-deleted item.
     *
     * Uses `withTrashed()` lookup since soft-deleted items are excluded from
     * default route-model binding. Idempotent: restoring a non-trashed item
     * returns the item unchanged.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the (possibly trashed) item. Example: 1
     *
     * @response 200 {
     *   "data": {"id":1,"workspace_id":1,"parent_id":null,"type":1,"title":"Note","data":{},"sort_order":0,"created_at":"2026-07-08T12:00:00.000000Z","updated_at":"2026-07-08T12:00:00.000000Z","deleted_at":null}
     * }
     * @response 404 {"title":"Not Found","status":404,"detail":"Not found."}
     */
    public function __invoke(int $id, Request $request): ItemData
    {
        $item = Item::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $item);

        $this->restoreItem->handle($item);

        return ItemData::fromModel($item->fresh());
    }
}
