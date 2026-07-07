<?php

namespace App\Http\Controllers\Api\Item;

use App\Actions\Item\DeleteItemAction;
use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemDestroyController extends Controller
{
    public function __construct(private readonly DeleteItemAction $deleteItem) {}

    /**
     * Soft-delete an item.
     *
     * @authenticated
     *
     * @urlParam item integer required The ID of the item. Example: 1
     *
     * @response 204 null
     */
    public function __invoke(Item $item): JsonResponse
    {
        Gate::authorize('delete', $item);

        $this->deleteItem->handle($item);

        return response()->json(null, 204);
    }
}
