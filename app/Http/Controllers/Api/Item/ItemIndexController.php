<?php

namespace App\Http\Controllers\Api\Item;

use App\Data\Api\ItemData;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Spatie\LaravelData\PaginatedDataCollection;

/**
 * @group Item Management
 *
 * Endpoints for managing items.
 */
final class ItemIndexController extends Controller
{
    private const int DefaultPerPage = 50;

    /**
     * List items.
     *
     * Regular users see only items in their own workspace. Admins see all
     * items. Use query parameters to filter the result set.
     *
     * @authenticated
     *
     * @queryParam per_page integer optional Items per page (default 10). Example: 10
     * @queryParam type integer optional Filter by ItemType value. Example: 1
     * @queryParam parent_id integer optional Filter by parent item ID. Pass 0 for root items only.
     * @queryParam with_trashed boolean optional Include soft-deleted items (admin only).
     *
     * @response 200 {
     *   "data": [{"id":1,"workspace_id":1,"parent_id":null,"type":1,"title":"Note","data":{},"sort_order":0,"created_at":null,"updated_at":null,"deleted_at":null}],
     *   "links": {"first":"...","last":"...","prev":null,"next":null},
     *   "meta": {"current_page":1,"per_page":10,"total":1}
     * }
     */
    public function __invoke(Request $request): PaginatedDataCollection
    {
        $user    = $request->user();
        $perPage = $request->integer('per_page', self::DefaultPerPage);

        $query = Item::query();

        if (! $user->hasRole(Role::Admin)) {
            $workspaceId = $user->workspace()->firstOrCreate([])->getKey();

            $query->where('workspace_id', $workspaceId);
        }

        if ($request->has('type')) {
            $query->where('type', $request->integer('type'));
        }

        if ($request->has('parent_id')) {
            $parentId = $request->integer('parent_id');

            if ($parentId === 0) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        if ($request->boolean('with_trashed') && $user->hasRole(Role::Admin)) {
            $query->withTrashed();
        }

        return ItemData::collect(
            $query->paginate($perPage),
            PaginatedDataCollection::class,
        );
    }
}
