<?php

namespace App\Http\Controllers\Api\Item;

use App\Actions\Item\CreateItemAction;
use App\Data\Api\Item\ItemStoreData;
use App\Data\Api\ItemData;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemStoreController extends Controller
{
    public function __construct(private readonly CreateItemAction $createItem) {}

    /**
     * Create a new item.
     *
     * If a `parent_id` is provided, the parent item must belong to the same
     * `workspace_id`; otherwise a 422 is returned.
     *
     * @authenticated
     *
     * @bodyParam workspace_id integer required The owning workspace ID. Example: 1
     * @bodyParam parent_id integer optional The parent item ID (must share the workspace).
     * @bodyParam type integer required The ItemType enum value (1=Note, 2=Todo, ...). Example: 1
     * @bodyParam title string required The item title (max 255 chars). Example: My Note
     * @bodyParam data object optional Arbitrary item data JSON object.
     * @bodyParam sort_order integer optional Sort weight (default 0). Example: 0
     *
     * @response 201 {
     *   "data": {"id":1,"workspace_id":1,"parent_id":null,"type":1,"title":"My Note","data":{},"sort_order":0,"created_at":"2026-07-08T12:00:00.000000Z","updated_at":"2026-07-08T12:00:00.000000Z","deleted_at":null}
     * }
     * @response 422 {"title":"Unprocessable Entity","status":422,"detail":"...","errors":{"parent_id":["This parent item does not belong to the selected workspace."]}}
     */
    public function __invoke(ItemStoreData $data, Request $request): JsonResponse
    {
        Gate::authorize('create', [Item::class, $data->workspace_id]);

        $item = $this->createItem->handle($data, $request->user());

        return ItemData::fromModel($item)
            ->toResponse($request)
            ->setStatusCode(201);
    }
}
